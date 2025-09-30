<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('classrooms', function (Blueprint $table) {
            if (!Schema::hasColumn('classrooms','class_teacher_id')) {
                $table->foreignId('class_teacher_id')
                      ->nullable()
                      ->constrained('staff')
                      ->nullOnDelete()
                      ->after('section');
            }
        });
    }

    public function down(): void {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_teacher_id');
        });
    }
};
