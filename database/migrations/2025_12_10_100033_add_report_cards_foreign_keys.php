<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('report_cards')) {
            return;
        }

        Schema::table('report_cards', function (Blueprint $table) {
            $foreignKeys = [
                'student_id' => ['table' => 'students', 'onDelete' => 'cascade'],
                'academic_year_id' => ['table' => 'academic_years', 'onDelete' => 'cascade'],
                'term_id' => ['table' => 'terms', 'onDelete' => 'cascade'],
                'classroom_id' => ['table' => 'classrooms', 'onDelete' => 'cascade'],
                'stream_id' => ['table' => 'streams', 'onDelete' => 'set null', 'nullable' => true],
                'published_by' => ['table' => 'staff', 'onDelete' => 'set null', 'nullable' => true],
            ];
            
            foreach ($foreignKeys as $column => $config) {
                if (Schema::hasTable($config['table']) && 
                    Schema::hasColumn('report_cards', $column)) {
                    // Check if foreign key already exists
                    $fkExists = DB::select("
                        SELECT COUNT(*) as count
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'report_cards' 
                        AND COLUMN_NAME = ?
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ", [$column]);
                    
                    if (!isset($fkExists[0]) || $fkExists[0]->count == 0) {
                        try {
                            $fk = $table->foreign($column)
                                ->references('id')
                                ->on($config['table']);
                            
                            if ($config['onDelete'] === 'cascade') {
                                $fk->onDelete('cascade');
                            } else {
                                $fk->onDelete('set null');
                            }
                        } catch (\Exception $e) {
                            // Ignore if already exists
                        }
                    }
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('report_cards')) {
            Schema::table('report_cards', function (Blueprint $table) {
                $columns = ['student_id', 'academic_year_id', 'term_id', 'classroom_id', 'stream_id', 'published_by'];
                foreach ($columns as $column) {
                    try {
                        $table->dropForeign(["report_cards_{$column}_foreign"]);
                    } catch (\Exception $e) {
                        try {
                            $table->dropForeign([$column]);
                        } catch (\Exception $e2) {
                            // Ignore
                        }
                    }
                }
            });
        }
    }
};

