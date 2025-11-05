<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/xxxx_xx_xx_add_weight_flags_to_exams.php
return new class extends Migration {
    public function up(): void {
        Schema::table('exams', function (Blueprint $t) {
            if (!Schema::hasColumn('exams','must_sit')) {
                $t->boolean('must_sit')->default(false);
            }
            if (!Schema::hasColumn('exams','max_marks')) {
                $t->unsignedInteger('max_marks')->default(100);
            }
            if (!Schema::hasColumn('exams','weight')) {
                $t->decimal('weight',5,2)->default(1);
            }
        });
    }
    public function down(): void {
        Schema::table('exams', function (Blueprint $t) {
            $t->dropColumn(['must_sit','max_marks','weight']);
        });
    }
};
