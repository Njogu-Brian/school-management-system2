<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_assignments')) {
            return;
        }

        if (Schema::hasColumn('student_assignments', 'drop_off_point_id')) {
            // Backfill morning/evening from legacy single column where empty.
            DB::table('student_assignments')
                ->whereNotNull('drop_off_point_id')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $updates = [];
                        if (empty($row->morning_drop_off_point_id)) {
                            $updates['morning_drop_off_point_id'] = $row->drop_off_point_id;
                        }
                        if (empty($row->evening_drop_off_point_id)) {
                            $updates['evening_drop_off_point_id'] = $row->drop_off_point_id;
                        }
                        if ($updates) {
                            DB::table('student_assignments')->where('id', $row->id)->update($updates);
                        }
                    }
                });

            Schema::table('student_assignments', function (Blueprint $table) {
                try {
                    $table->dropForeign(['drop_off_point_id']);
                } catch (\Throwable $e) {
                    // FK name may differ across environments; try raw drop below.
                }
            });

            try {
                DB::statement('ALTER TABLE `student_assignments` DROP FOREIGN KEY `student_assignments_drop_off_point_id_foreign`');
            } catch (\Throwable $e) {
                // Already dropped or different name.
            }

            Schema::table('student_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('student_assignments', 'drop_off_point_id')) {
                    $table->dropColumn('drop_off_point_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_assignments')) {
            return;
        }

        if (!Schema::hasColumn('student_assignments', 'drop_off_point_id')) {
            Schema::table('student_assignments', function (Blueprint $table) {
                $table->unsignedBigInteger('drop_off_point_id')->nullable()->after('student_id');
                $table->foreign('drop_off_point_id')
                    ->references('id')
                    ->on('drop_off_points')
                    ->nullOnDelete();
            });

            // Restore from evening preference, then morning.
            DB::table('student_assignments')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $legacy = $row->evening_drop_off_point_id ?: $row->morning_drop_off_point_id;
                        if ($legacy) {
                            DB::table('student_assignments')
                                ->where('id', $row->id)
                                ->update(['drop_off_point_id' => $legacy]);
                        }
                    }
                });
        }
    }
};
