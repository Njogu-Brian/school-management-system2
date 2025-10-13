<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('diaries', function (Blueprint $table) {
            if (!Schema::hasColumn('diaries','homework_id')) {
                $table->foreignId('homework_id')->nullable()->after('teacher_id')->constrained('homework')->nullOnDelete();
            }
            if (!Schema::hasColumn('diaries','is_homework')) {
                $table->boolean('is_homework')->default(false)->after('homework_id');
            }
        });
    }

    public function down(): void {
        Schema::table('diaries', function (Blueprint $table) {
            if (Schema::hasColumn('diaries','homework_id')) {
                $table->dropConstrainedForeignId('homework_id');
            }
            if (Schema::hasColumn('diaries','is_homework')) {
                $table->dropColumn('is_homework');
            }
        });
    }
};
