<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_marks', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_marks', 'is_absent')) {
                $table->boolean('is_absent')->default(false)->after('score_moderated');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_marks', function (Blueprint $table) {
            if (Schema::hasColumn('exam_marks', 'is_absent')) {
                $table->dropColumn('is_absent');
            }
        });
    }
};
