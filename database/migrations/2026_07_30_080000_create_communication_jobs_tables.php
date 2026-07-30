<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('tracking_id')->nullable()->index();
            $table->string('source', 40)->default('manual_bulk'); // manual_bulk|scheduled_comm|scheduled_fee|fee_reminder|payment|other
            $table->string('channel', 20); // sms|email|whatsapp
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 30)->default('pending')->index(); // pending|scheduled|running|paused|completed|cancelled|failed
            $table->string('pause_reason')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->nullableMorphs('source_ref'); // source_ref_type + source_ref_id
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'channel']);
            $table->index(['source', 'status']);
        });

        Schema::create('communication_job_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('communication_job_id')->constrained('communication_jobs')->cascadeOnDelete();
            $table->string('contact')->nullable();
            $table->string('name')->nullable();
            $table->string('recipient_type')->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending|sent|failed|skipped|cancelled
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('communication_log_id')->nullable();
            $table->timestamps();

            $table->index(['communication_job_id', 'status']);
            $table->index(['contact', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_job_recipients');
        Schema::dropIfExists('communication_jobs');
    }
};
