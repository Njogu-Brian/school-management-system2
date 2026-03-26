<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'subject_group_id')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                $fks = DB::select(
                    'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
                     AND REFERENCED_TABLE_NAME IS NOT NULL',
                    ['subjects', 'subject_group_id']
                );
                foreach ($fks as $fk) {
                    DB::statement('ALTER TABLE `subjects` DROP FOREIGN KEY `'.$fk->CONSTRAINT_NAME.'`');
                }
            } else {
                Schema::table('subjects', function (Blueprint $table) {
                    $table->dropForeign(['subject_group_id']);
                });
            }

            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('subject_group_id');
            });
        }

        Schema::dropIfExists('subject_groups');
    }

    public function down(): void
    {
        // Intentionally empty: restoring subject_groups requires re-running original migrations.
    }
};
