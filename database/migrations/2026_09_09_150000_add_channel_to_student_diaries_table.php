<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7G — separate teacher↔parent and admin↔parent conversation channels.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_diaries')) {
            return;
        }

        Schema::table('student_diaries', function (Blueprint $table) {
            if (! Schema::hasColumn('student_diaries', 'channel')) {
                $table->string('channel', 32)->default('teacher_parent')->after('student_id');
            }
        });

        DB::table('student_diaries')->where(function ($q) {
            $q->whereNull('channel')->orWhere('channel', '');
        })->update(['channel' => 'teacher_parent']);

        // Drop single-column unique on student_id (MySQL default name).
        try {
            Schema::table('student_diaries', function (Blueprint $table) {
                $table->dropUnique('student_diaries_student_id_unique');
            });
        } catch (\Throwable $e) {
            try {
                Schema::table('student_diaries', function (Blueprint $table) {
                    $table->dropUnique(['student_id']);
                });
            } catch (\Throwable $e2) {
                // already dropped
            }
        }

        try {
            Schema::table('student_diaries', function (Blueprint $table) {
                $table->unique(['student_id', 'channel'], 'student_diaries_student_channel_unique');
            });
        } catch (\Throwable $e) {
            // index may already exist
        }
    }

    public function down(): void
    {
        // Intentionally keep channel column to avoid destroying admin_parent threads.
    }
};
