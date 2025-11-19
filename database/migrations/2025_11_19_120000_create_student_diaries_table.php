<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_diaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->timestamps();
        });

        // Ensure all existing students receive a diary
        if (Schema::hasTable('students')) {
            DB::table('students')
                ->select('id')
                ->orderBy('id')
                ->chunkById(500, function ($students) {
                    $timestamp = Carbon::now();
                    $records = [];

                    foreach ($students as $student) {
                        $records[] = [
                            'student_id' => $student->id,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    }

                    if (!empty($records)) {
                        DB::table('student_diaries')->insertOrIgnore($records);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_diaries');
    }
};

