<?php

namespace Tests\Feature\Website;

use App\Models\Website\Page;
use App\Models\Website\SectionTemplate;
use App\Models\Website\WebsiteCta;
use App\Models\Website\WebsiteEvent;
use Database\Seeders\WebsiteSprints2130Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteSprints2130ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WebsiteSprints2130Seeder::class);
    }

    public function test_conversion_ctas_endpoint(): void
    {
        WebsiteCta::create([
            'name' => 'Apply',
            'cta_type' => 'apply_now',
            'label' => 'Apply Now',
            'url' => '/admissions',
            'placement' => 'global',
            'is_active' => true,
        ]);

        $this->getJson('/api/website/conversion/ctas')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_seo_schema_endpoint(): void
    {
        $this->getJson('/api/website/seo/schema')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    public function test_local_areas_seeded(): void
    {
        $this->getJson('/api/website/seo/local-areas')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(5, 'data');
    }

    public function test_assistant_accepts_page_path(): void
    {
        $this->postJson('/api/website/assistant/chat', [
            'message' => 'How do I apply?',
            'page_path' => '/admissions',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_event_registration_validation(): void
    {
        $event = WebsiteEvent::create([
            'title' => 'Open Day',
            'slug' => 'open-day',
            'description' => 'Visit us',
            'start_date' => now()->addWeek()->toDateString(),
            'registration_enabled' => true,
        ]);

        $this->postJson('/api/website/events/open-day/register', [])
            ->assertStatus(422);

        $this->postJson('/api/website/events/open-day/register', [
            'name' => 'Jane Parent',
            'email' => 'jane@example.com',
            'attendees' => 2,
        ])->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_section_templates_seeded(): void
    {
        $this->assertGreaterThanOrEqual(13, SectionTemplate::count());
    }

    public function test_page_builder_snapshot_service(): void
    {
        $page = Page::create([
            'name' => 'About',
            'slug' => 'about',
            'title' => 'About Us',
            'status' => Page::STATUS_PUBLISHED,
        ]);

        $builder = app(\App\Services\Website\PageBuilderService::class);
        $builder->addSectionFromTemplate($page, 'hero', 0);
        $snapshot = $builder->snapshot($page, 'Test version');

        $this->assertDatabaseHas('page_builder_snapshots', ['page_id' => $page->id, 'label' => 'Test version']);
        $page->sections()->delete();
        $builder->restoreSnapshot($snapshot);
        $this->assertEquals(1, $page->sections()->count());
    }
}
