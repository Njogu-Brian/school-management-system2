<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Sprint 6: Admissions Engine ──────────────────────────────────
        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->unique();
            $table->string('parent_name');
            $table->string('phone');
            $table->string('email');
            $table->string('child_name');
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('desired_class')->nullable();
            $table->string('previous_school')->nullable();
            $table->text('medical_notes')->nullable();
            $table->text('special_needs')->nullable();
            $table->enum('status', [
                'pending',
                'contacted',
                'assessment_booked',
                'approved',
                'rejected',
                'enrolled',
            ])->default('pending');
            $table->string('source')->default('website');
            $table->foreignId('assigned_staff')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessment_date')->nullable();
            $table->text('admission_notes')->nullable();
            $table->uuid('draft_token')->nullable()->unique();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->json('form_progress')->nullable();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('admission_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('admission_applications')->cascadeOnDelete();
            $table->enum('document_type', [
                'birth_certificate',
                'report_form',
                'passport_photo',
                'transfer_letter',
            ]);
            $table->string('file_path');
            $table->boolean('verified')->default(false);
            $table->timestamps();
        });

        // ── Sprint 8: Advanced CMS ───────────────────────────────────────
        Schema::create('website_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->default('header');
            $table->timestamps();
        });

        Schema::create('website_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('website_menus')->cascadeOnDelete();
            $table->string('title');
            $table->string('url');
            $table->foreignId('parent_id')->nullable()->constrained('website_menu_items')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('reusable_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('block_type');
            $table->longText('content')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->string('page_type');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('keywords')->nullable();
            $table->string('og_image')->nullable();
            $table->json('schema_markup')->nullable();
            $table->timestamps();

            $table->unique(['page_type', 'reference_id']);
        });

        Schema::create('page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('pages', function (Blueprint $table) {
            if (! Schema::hasColumn('pages', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('published_at');
            }
            if (! Schema::hasColumn('pages', 'preview_token')) {
                $table->uuid('preview_token')->nullable()->unique()->after('scheduled_at');
            }
        });

        // ── Sprint 9: Blog SEO ───────────────────────────────────────────
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('blog_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('blog_blog_category', function (Blueprint $table) {
            $table->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
            $table->foreignId('blog_category_id')->constrained('blog_categories')->cascadeOnDelete();
            $table->primary(['blog_id', 'blog_category_id']);
        });

        Schema::create('blog_blog_tag', function (Blueprint $table) {
            $table->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained('blog_tags')->cascadeOnDelete();
            $table->primary(['blog_id', 'blog_tag_id']);
        });

        Schema::table('blogs', function (Blueprint $table) {
            if (! Schema::hasColumn('blogs', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('published');
            }
            if (! Schema::hasColumn('blogs', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0)->after('is_featured');
            }
        });

        // ── Sprint 10: Media albums ──────────────────────────────────────
        Schema::create('media_albums', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::table('media_library', function (Blueprint $table) {
            if (! Schema::hasColumn('media_library', 'album_id')) {
                $table->foreignId('album_id')->nullable()->after('category')->constrained('media_albums')->nullOnDelete();
            }
            if (! Schema::hasColumn('media_library', 'video_url')) {
                $table->string('video_url')->nullable()->after('file_path');
            }
        });

        Schema::create('virtual_tour_stops', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('panorama_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Sprint 11: Marketing automation ──────────────────────────────
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->enum('status', ['active', 'unsubscribed', 'bounced'])->default('active');
            $table->string('source')->default('website');
            $table->timestamps();
        });

        Schema::create('campaign_logs', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_name');
            $table->string('type');
            $table->string('audience')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->enum('status', ['draft', 'scheduled', 'sent', 'failed'])->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('admission_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('admission_applications')->cascadeOnDelete();
            $table->timestamp('remind_at');
            $table->boolean('sent')->default(false);
            $table->timestamps();
        });

        // ── Sprint 12: Analytics ─────────────────────────────────────────
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('page');
            $table->string('visitor_id', 64)->nullable();
            $table->string('device')->nullable();
            $table->string('source')->nullable();
            $table->unsignedInteger('duration')->default(0);
            $table->timestamp('viewed_at')->useCurrent();
            $table->index(['page', 'viewed_at']);
        });

        Schema::create('conversion_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('page')->nullable();
            $table->json('metadata')->nullable();
            $table->string('visitor_id', 64)->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->index(['event_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_events');
        Schema::dropIfExists('page_views');
        Schema::dropIfExists('admission_reminders');
        Schema::dropIfExists('campaign_logs');
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('virtual_tour_stops');
        Schema::table('media_library', function (Blueprint $table) {
            if (Schema::hasColumn('media_library', 'album_id')) {
                $table->dropConstrainedForeignId('album_id');
            }
            if (Schema::hasColumn('media_library', 'video_url')) {
                $table->dropColumn('video_url');
            }
        });
        Schema::dropIfExists('media_albums');
        Schema::table('blogs', function (Blueprint $table) {
            if (Schema::hasColumn('blogs', 'is_featured')) {
                $table->dropColumn('is_featured');
            }
            if (Schema::hasColumn('blogs', 'views_count')) {
                $table->dropColumn('views_count');
            }
        });
        Schema::dropIfExists('blog_blog_tag');
        Schema::dropIfExists('blog_blog_category');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_categories');
        Schema::table('pages', function (Blueprint $table) {
            if (Schema::hasColumn('pages', 'scheduled_at')) {
                $table->dropColumn('scheduled_at');
            }
            if (Schema::hasColumn('pages', 'preview_token')) {
                $table->dropColumn('preview_token');
            }
        });
        Schema::dropIfExists('page_revisions');
        Schema::dropIfExists('seo_meta');
        Schema::dropIfExists('reusable_blocks');
        Schema::dropIfExists('website_menu_items');
        Schema::dropIfExists('website_menus');
        Schema::dropIfExists('admission_documents');
        Schema::dropIfExists('admission_applications');
    }
};
