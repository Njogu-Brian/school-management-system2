<?php

use App\Models\Academics\ClassroomSubject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reverts the "staff_id in unique key" design: one classroom_subjects row per
 * (classroom, stream, subject, year, term). Including staff_id caused UPDATE
 * conflicts when assigning a teacher (NULL → staff id) and duplicate slots.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('classroom_subjects')) {
            return;
        }

        if (self::indexExists('classroom_subjects', 'cls_sub_staff_unique')) {
            Schema::table('classroom_subjects', function (Blueprint $table) {
                $table->dropUnique('cls_sub_staff_unique');
            });
        }

        self::dedupeSlots();

        if (! self::indexExists('classroom_subjects', 'cls_sub_unique')) {
            Schema::table('classroom_subjects', function (Blueprint $table) {
                $table->unique(
                    ['classroom_id', 'stream_id', 'subject_id', 'academic_year_id', 'term_id'],
                    'cls_sub_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('classroom_subjects')) {
            return;
        }

        if (self::indexExists('classroom_subjects', 'cls_sub_unique')) {
            Schema::table('classroom_subjects', function (Blueprint $table) {
                $table->dropUnique('cls_sub_unique');
            });
        }

        if (! self::indexExists('classroom_subjects', 'cls_sub_staff_unique')) {
            Schema::table('classroom_subjects', function (Blueprint $table) {
                $table->unique(
                    ['classroom_id', 'stream_id', 'subject_id', 'staff_id', 'academic_year_id', 'term_id'],
                    'cls_sub_staff_unique'
                );
            });
        }
    }

    private static function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $indexName]
        );

        return count($rows) > 0;
    }

    private static function dedupeSlots(): void
    {
        $groups = DB::table('classroom_subjects')
            ->select(
                'classroom_id',
                'stream_id',
                'subject_id',
                'academic_year_id',
                'term_id',
                DB::raw('COUNT(*) as c')
            )
            ->groupBy('classroom_id', 'stream_id', 'subject_id', 'academic_year_id', 'term_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $g) {
            $q = ClassroomSubject::query()
                ->where('classroom_id', $g->classroom_id)
                ->where('subject_id', $g->subject_id);

            if ($g->stream_id === null) {
                $q->whereNull('stream_id');
            } else {
                $q->where('stream_id', $g->stream_id);
            }
            if ($g->academic_year_id === null) {
                $q->whereNull('academic_year_id');
            } else {
                $q->where('academic_year_id', $g->academic_year_id);
            }
            if ($g->term_id === null) {
                $q->whereNull('term_id');
            } else {
                $q->where('term_id', $g->term_id);
            }

            $rows = $q->orderByRaw('staff_id IS NULL ASC')
                ->orderByDesc('id')
                ->get();

            $keeper = $rows->first(fn ($r) => $r->staff_id !== null) ?? $rows->first();
            if (! $keeper) {
                continue;
            }

            foreach ($rows as $row) {
                if ((int) $row->id !== (int) $keeper->id) {
                    $row->delete();
                }
            }
        }
    }
};
