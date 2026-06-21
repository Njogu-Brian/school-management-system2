<?php

namespace Tests\Feature\Website;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseLayerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_live_operations_endpoint_is_registered(): void
    {
        $response = $this->getJson('/api/website/live/status');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['is_open', 'status_note']]);
    }

    public function test_public_showcase_endpoint(): void
    {
        $response = $this->getJson('/api/website/showcase');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['spotlights', 'competitions', 'erp_achievements']]);
    }

    public function test_school_assistant_requires_message(): void
    {
        $response = $this->postJson('/api/website/assistant/chat', []);

        $response->assertStatus(422);
    }

    public function test_community_referral_validation(): void
    {
        $response = $this->postJson('/api/website/community/referrals', []);

        $response->assertStatus(422);
    }

    public function test_parent_payments_require_auth(): void
    {
        $response = $this->getJson('/api/website/parent/children/1/payments/summary');

        $response->assertStatus(401);
    }

    public function test_executive_kpis_require_auth(): void
    {
        $response = $this->getJson('/api/website/executive/kpis');

        $response->assertStatus(401);
    }

    public function test_parent_homework_requires_auth(): void
    {
        $response = $this->getJson('/api/website/parent/children/1/homework');

        $response->assertStatus(401);
    }

    public function test_parent_payment_options_require_auth(): void
    {
        $response = $this->getJson('/api/website/parent/children/1/payments/options');

        $response->assertStatus(401);
    }

    public function test_staff_attendance_requires_auth(): void
    {
        $response = $this->getJson('/api/website/staff/attendance/class');

        $response->assertStatus(401);
    }

    public function test_mobile_homework_requires_auth(): void
    {
        $response = $this->getJson('/api/mobile/v1/parent/children/1/homework');

        $response->assertStatus(401);
    }
}
