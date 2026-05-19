<?php

namespace Tests\Feature;

use App\Jobs\DispatchCampaign;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignDispatchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private function dispatchHandling(Campaign $campaign): void
    {
        (new DispatchCampaign($campaign))->handle();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->user = User::factory()->create();
        Subscription::factory()->starter()->create(['user_id' => $this->user->id]);
    }

    public function test_dispatches_send_jobs_for_opted_in_contacts(): void
    {
        Contact::factory()->count(3)->create(['user_id' => $this->user->id]);

        $campaign = Campaign::factory()->scheduled()->create(['user_id' => $this->user->id]);

        $this->dispatchHandling($campaign);

        $campaign->refresh();
        $this->assertEquals(Campaign::STATUS_SENDING, $campaign->status);
        $this->assertEquals(3, $campaign->total_contacts);

        $this->assertDatabaseCount('whatsapp_messages', 3);
    }

    public function test_skips_opted_out_contacts(): void
    {
        Contact::factory()->create(['user_id' => $this->user->id, 'opted_in' => true]);
        Contact::factory()->create(['user_id' => $this->user->id, 'opted_in' => false]);

        $campaign = Campaign::factory()->scheduled()->create(['user_id' => $this->user->id]);

        $this->dispatchHandling($campaign);

        $campaign->refresh();
        $this->assertEquals(Campaign::STATUS_SENDING, $campaign->status);
        $this->assertEquals(1, $campaign->total_contacts);

        $this->assertDatabaseCount('whatsapp_messages', 1);
    }

    public function test_completes_when_no_contacts_match(): void
    {
        $campaign = Campaign::factory()->scheduled()->create([
            'user_id' => $this->user->id,
            'target_tags' => ['vip'],
        ]);

        $this->dispatchHandling($campaign);

        $campaign->refresh();
        $this->assertEquals(Campaign::STATUS_COMPLETED, $campaign->status);
        $this->assertEquals(0, $campaign->total_contacts);
    }

    public function test_filters_contacts_by_target_tags(): void
    {
        Contact::factory()->withTags(['vip'])->create(['user_id' => $this->user->id]);
        Contact::factory()->withTags(['regular'])->create(['user_id' => $this->user->id]);

        $campaign = Campaign::factory()->scheduled()->create([
            'user_id' => $this->user->id,
            'target_tags' => ['vip'],
        ]);

        $this->dispatchHandling($campaign);

        $campaign->refresh();
        $this->assertEquals(1, $campaign->total_contacts);
    }

    public function test_respects_quota_limit(): void
    {
        Contact::factory()->count(5)->create(['user_id' => $this->user->id]);

        $this->user->subscription->update(['messages_limit' => 3, 'messages_sent' => 0]);

        $campaign = Campaign::factory()->scheduled()->create(['user_id' => $this->user->id]);

        $this->dispatchHandling($campaign);

        $campaign->refresh();
        $this->assertEquals(3, $campaign->total_contacts);
        $this->assertEquals(3, $this->user->subscription->fresh()->messages_sent);
    }

    public function test_cancels_when_quota_exhausted(): void
    {
        Contact::factory()->create(['user_id' => $this->user->id]);

        $this->user->subscription->update([
            'messages_limit' => 10,
            'messages_sent' => 10,
        ]);

        $campaign = Campaign::factory()->scheduled()->create(['user_id' => $this->user->id]);

        $this->dispatchHandling($campaign);

        $campaign->refresh();
        $this->assertEquals(Campaign::STATUS_CANCELLED, $campaign->status);
        $this->assertDatabaseCount('whatsapp_messages', 0);
    }

    public function test_creates_send_jobs_for_each_contact(): void
    {
        Contact::factory()->count(12)->create(['user_id' => $this->user->id]);

        $campaign = Campaign::factory()->scheduled()->create(['user_id' => $this->user->id]);

        $this->dispatchHandling($campaign);

        $campaign->refresh();
        $this->assertEquals(12, $campaign->total_contacts);

        Queue::assertPushed(SendWhatsAppMessage::class, 12);
    }
}
