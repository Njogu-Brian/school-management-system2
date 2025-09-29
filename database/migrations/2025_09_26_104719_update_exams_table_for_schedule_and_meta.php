<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // Ensure columns exist / adjust as needed
            if (!Schema::hasColumn('exams', 'type')) {
                $table->enum('type', ['opener','midterm','endterm','cat','rat'])->after('name');
            } else {
                // If it's a string and you want enum, comment this out unless you want to modify
                // $table->enum('type', ['opener','midterm','endterm','cat','rat'])->change();
            }

            if (!Schema::hasColumn('exams', 'modality')) {
                $table->enum('modality', ['physical','online'])->default('physical')->after('type');
            }

            foreach ([
                'academic_year_id' => 'academic_years',
                'term_id'          => 'terms',
                'classroom_id'     => 'classrooms',
                'stream_id'        => 'streams',
                'subject_id'       => 'subjects',
            ] as $col => $ref) {
                if (!Schema::hasColumn('exams', $col)) {
                    $table->foreignId($col)->nullable($col==='stream_id')->constrained($ref);
                }
            }

            if (!Schema::hasColumn('exams', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users');
            }

            if (!Schema::hasColumn('exams', 'starts_on')) {
                $table->dateTime('starts_on')->nullable();
            }
            if (!Schema::hasColumn('exams', 'ends_on')) {
                $table->dateTime('ends_on')->nullable();
            }
            if (!Schema::hasColumn('exams', 'max_marks')) {
                $table->integer('max_marks')->default(100);
            }
            if (!Schema::hasColumn('exams', 'weight')) {
                $table->decimal('weight', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('exams', 'status')) {
                $table->enum('status', ['draft','published','archived'])->default('draft');
            }
            if (!Schema::hasColumn('exams', 'published_at')) {
                $table->timestamp('published_at')->nullable();
            }
            if (!Schema::hasColumn('exams', 'locked_at')) {
                $table->timestamp('locked_at')->nullable();
            }
            if (!Schema::hasColumn('exams', 'settings')) {
                $table->json('settings')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // keep it simple (no destructive down to avoid data loss)
        });
    }
};
