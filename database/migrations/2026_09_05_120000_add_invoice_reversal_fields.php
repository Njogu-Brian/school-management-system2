<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'reversed_by')) {
                $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('invoices', 'reversal_reason')) {
                $table->text('reversal_reason')->nullable()->after('reversed_by');
            }
        });

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('invoices')
            ->where('status', 'reversed')
            ->whereNull('reversed_at')
            ->update(['reversed_at' => now()]);

        $statusCol = collect(DB::select('SHOW COLUMNS FROM invoices LIKE \'status\''))->first();
        if ($statusCol && str_contains(strtolower((string) $statusCol->Type), 'enum')) {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'unpaid'");
        }

        $oldUnique = DB::selectOne("
            SELECT COUNT(1) AS c
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'invoices'
              AND INDEX_NAME = 'invoices_student_year_term_unique'
        ");
        if ($oldUnique && (int) $oldUnique->c > 0) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropUnique('invoices_student_year_term_unique');
            });
        }

        if (! Schema::hasColumn('invoices', 'active_student_term_key')) {
            DB::statement("
                ALTER TABLE invoices
                ADD COLUMN active_student_term_key VARCHAR(64)
                GENERATED ALWAYS AS (
                    CASE
                        WHEN `deleted_at` IS NULL AND `reversed_at` IS NULL
                        THEN CONCAT(`student_id`, '-', `year`, '-', `term`)
                        ELSE NULL
                    END
                ) STORED
            ");
        }

        $newUnique = DB::selectOne("
            SELECT COUNT(1) AS c
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'invoices'
              AND INDEX_NAME = 'invoices_active_student_term_unique'
        ");
        if (! $newUnique || (int) $newUnique->c === 0) {
            DB::statement('ALTER TABLE invoices ADD UNIQUE INDEX invoices_active_student_term_unique (active_student_term_key)');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $newUnique = DB::selectOne("
                SELECT COUNT(1) AS c
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'invoices'
                  AND INDEX_NAME = 'invoices_active_student_term_unique'
            ");
            if ($newUnique && (int) $newUnique->c > 0) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->dropUnique('invoices_active_student_term_unique');
                });
            }

            if (Schema::hasColumn('invoices', 'active_student_term_key')) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->dropColumn('active_student_term_key');
                });
            }

            $oldUnique = DB::selectOne("
                SELECT COUNT(1) AS c
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'invoices'
                  AND INDEX_NAME = 'invoices_student_year_term_unique'
            ");
            if (! $oldUnique || (int) $oldUnique->c === 0) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->unique(['student_id', 'year', 'term'], 'invoices_student_year_term_unique');
                });
            }
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'reversal_reason')) {
                $table->dropColumn('reversal_reason');
            }
            if (Schema::hasColumn('invoices', 'reversed_by')) {
                $table->dropConstrainedForeignId('reversed_by');
            }
        });
    }
};
