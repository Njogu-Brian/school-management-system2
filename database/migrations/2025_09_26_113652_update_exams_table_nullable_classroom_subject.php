<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Make classroom_id and subject_id nullable
            if (Schema::hasColumn('exams', 'classroom_id')) {
                $table->unsignedBigInteger('classroom_id')->nullable()->change();
            }

            if (Schema::hasColumn('exams', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Revert back to required if rollback
            if (Schema::hasColumn('exams', 'classroom_id')) {
                $table->unsignedBigInteger('classroom_id')->nullable(false)->change();
            }

            if (Schema::hasColumn('exams', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable(false)->change();
            }
        });
    }
};
