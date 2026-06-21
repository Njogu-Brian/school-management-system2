<?php

namespace Tests\Feature\Website;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseLayerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_live_operations_endpoint(): void
    {
        $this->getJson('/api/website/live/status')
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['is_open', 'status_note']]);
    }

    public function test_public_showcase_endpoint(): void
    {
        $this->getJson('/api/website/showcase')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['spotlights', 'competitions']]);
    }

    public function test_school_assistant_requires_message(): void
    {
        $this->postJson('/api/website/assistant/chat', [])->assertStatus(422);
    }

    public function test_community_referral_validation(): void
    {
        $this->postJson('/api/website/community/referrals', [])->assertStatus(422);
    }

    public function test_website_ai_generate_requires_auth(): void
    {
        $this->postJson('/api/website/ai/generate', [
            'content_type' => 'blog',
            'subject' => 'Test',
        ])->assertStatus(401);
    }
}
