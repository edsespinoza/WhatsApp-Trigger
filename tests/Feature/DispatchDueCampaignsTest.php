<?php

namespace Tests\Feature;

use App\Jobs\DispatchCampaign;
use App\Models\Campaign;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchDueCampaignsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->user = User::factory()->create();
        Subscription::factory()->starter()->create(['user_id' => $this->user->id]);
    }

    public function test_dispatches_due_campaigns(): void
    {
        Campaign::factory()->scheduled()->count(3)->create(['user_id' => $this->user->id]);

        $exitCode = Artisan::call('whatstrigger:dispatch-due');

        $this->assertEquals(Command::SUCCESS, $exitCode);
        Queue::assertPushed(DispatchCampaign::class, 3);
    }

    public function test_skips_future_campaigns(): void
    {
        Campaign::factory()->create([
            'user_id' => $this->user->id,
            'scheduled_at' => now()->addDay(),
            'status' => Campaign::STATUS_SCHEDULED,
        ]);

        Artisan::call('whatstrigger:dispatch-due');

        Queue::assertPushed(DispatchCampaign::class, 0);
    }

    public function test_skips_non_scheduled_campaigns(): void
    {
        Campaign::factory()->create([
            'user_id' => $this->user->id,
            'scheduled_at' => now()->subHour(),
            'status' => Campaign::STATUS_DRAFT,
        ]);

        Artisan::call('whatstrigger:dispatch-due');

        Queue::assertPushed(DispatchCampaign::class, 0);
    }

    public function test_dry_run_does_not_dispatch(): void
    {
        Campaign::factory()->scheduled()->count(2)->create(['user_id' => $this->user->id]);

        $exitCode = Artisan::call('whatstrigger:dispatch-due', ['--dry-run' => true]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        Queue::assertPushed(DispatchCampaign::class, 0);

        $output = Artisan::output();
        $this->assertStringContainsString('encontrada(s)', $output);
    }

    public function test_messages_count_in_output(): void
    {
        Campaign::factory()->scheduled()->count(2)->create(['user_id' => $this->user->id]);

        Artisan::call('whatstrigger:dispatch-due');

        $output = Artisan::output();
        $this->assertStringContainsString('2 campanha(s) despachada(s).', $output);
    }

    public function test_handles_empty_due_campaigns(): void
    {
        $exitCode = Artisan::call('whatstrigger:dispatch-due');

        $this->assertEquals(Command::SUCCESS, $exitCode);
        Queue::assertPushed(DispatchCampaign::class, 0);

        $output = Artisan::output();
        $this->assertStringContainsString('0 campanha(s) despachada(s).', $output);
    }
}
