<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_types', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->enum('calculation_method', ['average','sum','weighted','best_of','pass_fail','cbc'])->default('average');
            // use decimal(..., ..., true) for unsigned
            $t->decimal('default_min_mark', 6, 2, true)->nullable();
            $t->decimal('default_max_mark', 6, 2, true)->nullable();
            $t->timestamps();
        });

        Schema::create('exam_groups', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->foreignId('exam_type_id')->nullable()->constrained('exam_types');
            $t->foreignId('academic_year_id')->constrained('academic_years');
            $t->foreignId('term_id')->constrained('terms');
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->foreignId('created_by')->nullable()->constrained('users');
            $t->timestamps();
        });

        Schema::table('exams', function (Blueprint $t) {
            $t->foreignId('exam_group_id')->nullable()->after('id')->constrained('exam_groups');
            $t->boolean('publish_exam')->default(false)->after('status');
            $t->boolean('publish_result')->default(false)->after('publish_exam');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $t) {
            if (Schema::hasColumn('exams','publish_result')) $t->dropColumn('publish_result');
            if (Schema::hasColumn('exams','publish_exam')) $t->dropColumn('publish_exam');
            if (Schema::hasColumn('exams','exam_group_id')) $t->dropConstrainedForeignId('exam_group_id');
        });
        Schema::dropIfExists('exam_groups');
        Schema::dropIfExists('exam_types');
    }
};
