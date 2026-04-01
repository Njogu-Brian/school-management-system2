<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_sessions')) {
            Schema::create('exam_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_type_id')->constrained('exam_types')->cascadeOnDelete();
                $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
                $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
                $table->foreignId('stream_id')->nullable()->constrained('streams')->nullOnDelete();
                $table->string('name');
                $table->enum('modality', ['physical', 'online'])->default('physical');
                $table->decimal('weight', 5, 2)->unsigned()->default(100);
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->string('status', 32)->default('draft');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('exams') && ! Schema::hasColumn('exams', 'exam_session_id')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->foreignId('exam_session_id')->nullable()->after('id')->constrained('exam_sessions')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('exams', 'exam_session_id')) {
            return;
        }

        // Backfill: one session per (type, year, term, class, stream) for subject papers.
        $groups = DB::table('exams')
            ->select('exam_type_id', 'academic_year_id', 'term_id', 'classroom_id', 'stream_id')
            ->whereNotNull('subject_id')
            ->whereNotNull('exam_type_id')
            ->whereNotNull('classroom_id')
            ->distinct()
            ->get();

        foreach ($groups as $row) {
            $exists = DB::table('exam_sessions')
                ->where('exam_type_id', $row->exam_type_id)
                ->where('academic_year_id', $row->academic_year_id)
                ->where('term_id', $row->term_id)
                ->where('classroom_id', $row->classroom_id)
                ->when($row->stream_id, fn ($q) => $q->where('stream_id', $row->stream_id), fn ($q) => $q->whereNull('stream_id'))
                ->value('id');

            if ($exists) {
                $sessionId = (int) $exists;
            } else {
                $typeName = DB::table('exam_types')->where('id', $row->exam_type_id)->value('name') ?? 'Exam';
                $className = DB::table('classrooms')->where('id', $row->classroom_id)->value('name') ?? 'Class';
                $sessionId = (int) DB::table('exam_sessions')->insertGetId([
                    'exam_type_id' => $row->exam_type_id,
                    'academic_year_id' => $row->academic_year_id,
                    'term_id' => $row->term_id,
                    'classroom_id' => $row->classroom_id,
                    'stream_id' => $row->stream_id,
                    'name' => $typeName.' — '.$className,
                    'modality' => 'physical',
                    'weight' => 100,
                    'status' => 'draft',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $q = DB::table('exams')
                ->where('exam_type_id', $row->exam_type_id)
                ->where('academic_year_id', $row->academic_year_id)
                ->where('term_id', $row->term_id)
                ->where('classroom_id', $row->classroom_id)
                ->whereNotNull('subject_id');

            if ($row->stream_id) {
                $q->where('stream_id', $row->stream_id);
            } else {
                $q->whereNull('stream_id');
            }

            $q->update(['exam_session_id' => $sessionId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exams') && Schema::hasColumn('exams', 'exam_session_id')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropConstrainedForeignId('exam_session_id');
            });
        }

        Schema::dropIfExists('exam_sessions');
    }
};
