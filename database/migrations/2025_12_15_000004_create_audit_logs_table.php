<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('user_type')->nullable(); // App\Models\User
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('event'); // created, updated, deleted, exported, generated, published, etc.
                $table->string('auditable_type'); // App\Models\Academics\ReportCard
                $table->unsignedBigInteger('auditable_id');
                $table->text('old_values')->nullable(); // JSON
                $table->text('new_values')->nullable(); // JSON
                $table->text('url')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->text('tags')->nullable(); // JSON array of tags
                $table->timestamps();

                $table->index(['user_type', 'user_id']);
                $table->index(['auditable_type', 'auditable_id']);
                $table->index('event');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

