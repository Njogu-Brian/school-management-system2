<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('exam_marks', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_marks', 'final_score')) {
                $table->decimal('final_score', 7, 2)->nullable()->after('endterm_score');
            }
        });
    }

    public function down(): void {
        Schema::table('exam_marks', function (Blueprint $table) {
            $table->dropColumn('final_score');
        });
    }
};
