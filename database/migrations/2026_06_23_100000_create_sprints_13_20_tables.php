<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Sprint 13: AI ────────────────────────────────────────────────
        Schema::create('ai_content_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('content_type');
            $table->text('prompt');
            $table->longText('output')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamps();
        });

        Schema::create('ai_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('session_key')->unique();
            $table->json('context')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ai_chat_sessions')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('message');
            $table->timestamps();
        });

        // ── Sprint 14: Student showcase ──────────────────────────────────
        Schema::create('student_spotlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('title');
            $table->text('story')->nullable();
            $table->string('achievement')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('published')->default(false);
            $table->timestamps();
        });

        Schema::create('website_competitions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('date')->nullable();
            $table->string('location')->nullable();
            $table->string('category')->nullable();
            $table->string('result')->nullable();
            $table->boolean('published')->default(true);
            $table->timestamps();
        });

        // ── Sprint 15: Live operations ───────────────────────────────────
        Schema::create('school_meals', function (Blueprint $table) {
            $table->id();
            $table->date('meal_date');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->nullable();
            $table->string('breakfast')->nullable();
            $table->string('lunch')->nullable();
            $table->string('snack')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('meal_date');
        });

        // ── Sprint 17: Payment automation ────────────────────────────────
        Schema::create('payment_plan_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('requested_amount', 12, 2)->nullable();
            $table->unsignedTinyInteger('installment_count')->default(3);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'completed'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // ── Sprint 18: Community ─────────────────────────────────────────
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('referrer_name');
            $table->string('referrer_phone');
            $table->string('referrer_email')->nullable();
            $table->string('referred_name');
            $table->string('referred_phone')->nullable();
            $table->string('referred_email')->nullable();
            $table->foreignId('admitted_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->enum('status', ['pending', 'contacted', 'admitted', 'rewarded'])->default('pending');
            $table->string('reward_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('alumni_stories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('graduation_year')->nullable();
            $table->string('headline');
            $table->text('story');
            $table->string('photo')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();
        });

        Schema::create('prayer_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->text('request');
            $table->boolean('is_anonymous')->default(false);
            $table->enum('status', ['pending', 'approved', 'prayed', 'archived'])->default('pending');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        // ── Sprint 20: Executive intelligence ────────────────────────────
        Schema::create('executive_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_type');
            $table->string('severity')->default('info');
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executive_alerts');
        Schema::dropIfExists('prayer_requests');
        Schema::dropIfExists('alumni_stories');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('payment_plan_requests');
        Schema::dropIfExists('school_meals');
        Schema::dropIfExists('website_competitions');
        Schema::dropIfExists('student_spotlights');
        Schema::dropIfExists('ai_chat_messages');
        Schema::dropIfExists('ai_chat_sessions');
        Schema::dropIfExists('ai_content_logs');
    }
};
