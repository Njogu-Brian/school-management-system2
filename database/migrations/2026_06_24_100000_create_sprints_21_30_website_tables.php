<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Sprint 21: Visual page builder ───────────────────────────────
        Schema::create('section_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->json('default_content')->nullable();
            $table->json('settings')->nullable();
            $table->string('preview_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('page_builder_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->json('sections')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('page_id');
        });

        Schema::create('page_builder_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->json('sections');
            $table->string('label')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ── Sprint 22: Advanced media ────────────────────────────────────
        Schema::create('media_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('media_taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_tag_id')->constrained('media_tags')->cascadeOnDelete();
            $table->morphs('taggable');
            $table->timestamps();
            $table->unique(['media_tag_id', 'taggable_id', 'taggable_type'], 'media_tag_unique');
        });

        Schema::table('media_library', function (Blueprint $table) {
            if (! Schema::hasColumn('media_library', 'focal_x')) {
                $table->unsignedSmallInteger('focal_x')->nullable()->after('alt_text');
                $table->unsignedSmallInteger('focal_y')->nullable()->after('focal_x');
                $table->timestamp('scheduled_publish_at')->nullable()->after('focal_y');
                $table->string('embed_url')->nullable()->after('video_url');
                $table->string('embed_provider')->nullable()->after('embed_url');
                $table->string('optimized_path')->nullable()->after('file_path');
            }
        });

        // ── Sprint 23: SEO ───────────────────────────────────────────────
        Schema::create('seo_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('target_url')->nullable();
            $table->unsignedSmallInteger('position')->nullable();
            $table->unsignedInteger('search_volume')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->timestamps();
        });

        Schema::create('service_area_pages', function (Blueprint $table) {
            $table->id();
            $table->string('area_name');
            $table->string('slug')->unique();
            $table->string('headline');
            $table->text('description')->nullable();
            $table->string('map_embed')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();
        });

        // ── Sprint 24: Conversion engine ─────────────────────────────────
        Schema::create('website_ctas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cta_type');
            $table->string('label');
            $table->string('url')->nullable();
            $table->string('placement')->default('global');
            $table->json('pages')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();
        });

        Schema::create('exit_intent_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('button_label')->default('Book a School Tour');
            $table->string('button_url')->nullable();
            $table->string('pages')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->timestamps();
        });

        Schema::create('lead_magnets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('published')->default(false);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
        });

        Schema::create('lead_magnet_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_magnet_id')->constrained('lead_magnets')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // ── Sprint 25: Testimonials ──────────────────────────────────────
        Schema::create('testimonial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('testimonial_category_testimonial', function (Blueprint $table) {
            $table->foreignId('testimonial_category_id')->constrained('testimonial_categories')->cascadeOnDelete();
            $table->foreignId('testimonial_id')->constrained('testimonials')->cascadeOnDelete();
            $table->primary(['testimonial_category_id', 'testimonial_id'], 'testimonial_cat_pivot_primary');
        });

        Schema::table('testimonials', function (Blueprint $table) {
            if (! Schema::hasColumn('testimonials', 'star_rating')) {
                $table->unsignedTinyInteger('star_rating')->nullable()->after('message');
                $table->string('category_type')->nullable()->after('relationship');
            }
        });

        // ── Sprint 26: Event registrations ───────────────────────────────
        Schema::create('website_event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_event_id')->constrained('website_events')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email');
            $table->unsignedSmallInteger('attendees')->default(1);
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamps();
        });

        Schema::table('website_events', function (Blueprint $table) {
            if (! Schema::hasColumn('website_events', 'countdown_enabled')) {
                $table->boolean('countdown_enabled')->default(true)->after('registration_enabled');
                $table->text('recap_content')->nullable()->after('description');
                $table->string('gallery_album_slug')->nullable()->after('recap_content');
            }
        });

        // ── Sprint 27: Community ─────────────────────────────────────────
        Schema::create('family_stories', function (Blueprint $table) {
            $table->id();
            $table->string('family_name');
            $table->text('story');
            $table->string('cover_image')->nullable();
            $table->boolean('published')->default(false);
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });

        Schema::table('prayer_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('prayer_requests', 'featured')) {
                $table->boolean('featured')->default(false)->after('is_public');
                $table->boolean('answered')->default(false)->after('featured');
                $table->text('answered_testimony')->nullable()->after('answered');
            }
        });

        // ── Sprint 28: Chatbot knowledge base ────────────────────────────
        Schema::create('assistant_knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('topic');
            $table->text('content');
            $table->json('page_context')->nullable();
            $table->boolean('published')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();
        });

        // ── Sprint 29: Content calendar ──────────────────────────────────
        Schema::create('content_calendar', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type');
            $table->date('publish_date')->nullable();
            $table->enum('status', ['idea', 'draft', 'scheduled', 'published'])->default('idea');
            $table->text('notes')->nullable();
            $table->foreignId('ai_content_log_id')->nullable()->constrained('ai_content_logs')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_calendar');
        Schema::dropIfExists('assistant_knowledge_articles');
        Schema::table('prayer_requests', function (Blueprint $table) {
            foreach (['answered_testimony', 'answered', 'featured'] as $col) {
                if (Schema::hasColumn('prayer_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('family_stories');
        Schema::table('website_events', function (Blueprint $table) {
            foreach (['gallery_album_slug', 'recap_content', 'countdown_enabled'] as $col) {
                if (Schema::hasColumn('website_events', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('website_event_registrations');
        Schema::table('testimonials', function (Blueprint $table) {
            if (Schema::hasColumn('testimonials', 'star_rating')) {
                $table->dropColumn(['star_rating', 'category_type']);
            }
        });
        Schema::dropIfExists('testimonial_category_testimonial');
        Schema::dropIfExists('testimonial_categories');
        Schema::dropIfExists('lead_magnet_downloads');
        Schema::dropIfExists('lead_magnets');
        Schema::dropIfExists('exit_intent_campaigns');
        Schema::dropIfExists('website_ctas');
        Schema::dropIfExists('service_area_pages');
        Schema::dropIfExists('seo_keywords');
        Schema::table('media_library', function (Blueprint $table) {
            foreach (['optimized_path', 'embed_provider', 'embed_url', 'scheduled_publish_at', 'focal_y', 'focal_x'] as $col) {
                if (Schema::hasColumn('media_library', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('media_taggables');
        Schema::dropIfExists('media_tags');
        Schema::dropIfExists('page_builder_snapshots');
        Schema::dropIfExists('page_builder_drafts');
        Schema::dropIfExists('section_templates');
    }
};
