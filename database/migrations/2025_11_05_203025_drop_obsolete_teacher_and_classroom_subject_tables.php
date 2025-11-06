<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop foreign keys first if they exist (best-effort)
        if (Schema::hasTable('classroom_subject')) {
            Schema::drop('classroom_subject');
        }

        if (Schema::hasTable('teachers')) {
            Schema::drop('teachers');
        }
    }

    public function down(): void
    {
        // Recreate minimal structures (in case of rollback)

        if (!Schema::hasTable('classroom_subject')) {
            Schema::create('classroom_subject', function (Blueprint $t) {
                $t->id();
                $t->foreignId('classroom_id')->constrained('classrooms');
                $t->foreignId('subject_id')->constrained('subjects');
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('teachers')) {
            Schema::create('teachers', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('email')->unique();
                $t->string('password');
                $t->string('class');
                $t->timestamps();
            });
        }
    }
};
