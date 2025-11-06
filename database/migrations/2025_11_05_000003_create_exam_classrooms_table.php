<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('grading_schemes')) {
            Schema::create('grading_schemes', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('type')->default('percentage');
                $t->json('meta')->nullable();
                $t->boolean('is_default')->default(false);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('grading_bands')) {
            Schema::create('grading_bands', function (Blueprint $t) {
                $t->id();
                $t->foreignId('grading_scheme_id')->constrained();
                $t->decimal('min', 6, 2, true);
                $t->decimal('max', 6, 2, true);
                $t->string('label');
                $t->string('descriptor')->nullable();
                $t->decimal('rank', 5, 2, true)->default(0);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('grading_scheme_mappings')) {
            Schema::create('grading_scheme_mappings', function (Blueprint $t) {
                $t->id();
                $t->foreignId('grading_scheme_id')->constrained();
                $t->foreignId('classroom_id')->nullable()->constrained('classrooms');
                $t->string('level_key')->nullable();
                $t->timestamps();
            });
        }

        if (Schema::hasTable('exam_grades')) {
            DB::transaction(function () {
                $types = DB::table('exam_grades')->select('exam_type')->distinct()->pluck('exam_type');
                foreach ($types as $type) {
                    $exists = DB::table('grading_schemes')->where('name', $type.' scheme')->exists();
                    if ($exists) continue;

                    $schemeId = DB::table('grading_schemes')->insertGetId([
                        'name' => $type.' scheme',
                        'type' => 'percentage',
                        'is_default' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $rows = DB::table('exam_grades')->where('exam_type',$type)->get();
                    foreach ($rows as $r) {
                        DB::table('grading_bands')->insert([
                            'grading_scheme_id' => $schemeId,
                            'min' => $r->percent_from,
                            'max' => $r->percent_upto,
                            'label' => $r->grade_name,
                            'descriptor' => $r->description ?? $r->grade_name,
                            'rank' => $r->grade_point ?? 0,
                            'created_at'=>now(),'updated_at'=>now(),
                        ]);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_scheme_mappings');
        Schema::dropIfExists('grading_bands');
        Schema::dropIfExists('grading_schemes');
    }
};
