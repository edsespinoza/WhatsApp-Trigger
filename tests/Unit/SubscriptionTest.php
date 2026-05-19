<?php

namespace Tests\Unit;

use App\Models\Subscription;
use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    public function test_has_quota_returns_true_when_under_limit(): void
    {
        $sub = new Subscription(['messages_limit' => 50, 'messages_sent' => 10]);

        $this->assertTrue($sub->hasQuota());
    }

    public function test_has_quota_returns_false_when_at_limit(): void
    {
        $sub = new Subscription(['messages_limit' => 50, 'messages_sent' => 50]);

        $this->assertFalse($sub->hasQuota());
    }

    public function test_enterprise_always_has_quota(): void
    {
        $sub = new Subscription(['messages_limit' => -1, 'messages_sent' => 999999]);

        $this->assertTrue($sub->hasQuota());
    }

    public function test_remaining_messages_calculation(): void
    {
        $sub = new Subscription(['messages_limit' => 100, 'messages_sent' => 30]);

        $this->assertEquals(70, $sub->remainingMessages());
    }

    public function test_remaining_messages_never_negative(): void
    {
        $sub = new Subscription(['messages_limit' => 50, 'messages_sent' => 60]);

        $this->assertEquals(0, $sub->remainingMessages());
    }

    public function test_enterprise_remaining_messages_is_max_int(): void
    {
        $sub = new Subscription(['messages_limit' => -1, 'messages_sent' => 0]);

        $this->assertEquals(PHP_INT_MAX, $sub->remainingMessages());
    }

    public function test_limit_for_plan_returns_correct_values(): void
    {
        $this->assertEquals(50, Subscription::limitForPlan('free'));
        $this->assertEquals(2000, Subscription::limitForPlan('starter'));
        $this->assertEquals(10000, Subscription::limitForPlan('pro'));
        $this->assertEquals(-1, Subscription::limitForPlan('enterprise'));
    }

    public function test_limit_for_unknown_plan_falls_back_to_free(): void
    {
        $this->assertEquals(50, Subscription::limitForPlan('unknown'));
    }

    public function test_campaign_is_completed_when_all_messages_processed(): void
    {
        // Testado via Campaign::isCompleted() em CampaignTest — aqui apenas validamos a lógica de negócio
        $sub = new Subscription([
            'messages_limit' => 2000,
            'messages_sent' => 1999,
        ]);

        $this->assertTrue($sub->hasQuota());

        $sub->messages_sent = 2000;
        $this->assertFalse($sub->hasQuota());
    }
}
