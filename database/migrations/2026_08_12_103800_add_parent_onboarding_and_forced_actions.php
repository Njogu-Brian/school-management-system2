<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'credentials_sent_at')) {
                $table->timestamp('credentials_sent_at')->nullable()->after('parent_profile_review_required');
            }
            if (! Schema::hasColumn('users', 'credentials_sent_via')) {
                $table->string('credentials_sent_via', 64)->nullable()->after('credentials_sent_at');
            }
            if (! Schema::hasColumn('users', 'first_app_login_at')) {
                $table->timestamp('first_app_login_at')->nullable()->after('credentials_sent_via');
            }
            if (! Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('first_app_login_at');
            }
            if (! Schema::hasColumn('users', 'profile_completed_at')) {
                $table->timestamp('profile_completed_at')->nullable()->after('password_changed_at');
            }
        });

        if (! Schema::hasTable('parent_forced_actions')) {
            Schema::create('parent_forced_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('parent_info_id')->constrained('parent_info')->cascadeOnDelete();
                $table->string('type', 64);
                $table->string('title');
                $table->json('payload')->nullable();
                $table->unsignedSmallInteger('priority')->default(100);
                $table->boolean('blocking')->default(true);
                $table->string('status', 32)->default('pending');
                $table->timestamp('due_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['parent_info_id', 'status']);
                $table->index(['user_id', 'status']);
                $table->index(['type', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_forced_actions');

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'credentials_sent_at',
                'credentials_sent_via',
                'first_app_login_at',
                'password_changed_at',
                'profile_completed_at',
            ] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
