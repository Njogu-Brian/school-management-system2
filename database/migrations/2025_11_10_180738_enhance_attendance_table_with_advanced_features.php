<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            // Reason code and excuse tracking
            if (!Schema::hasColumn('attendance', 'reason_code_id')) {
                $table->unsignedBigInteger('reason_code_id')->nullable()->after('reason');
            }
            if (!Schema::hasColumn('attendance', 'is_excused')) {
                $table->boolean('is_excused')->default(false)->after('reason_code_id');
            }
            if (!Schema::hasColumn('attendance', 'is_medical_leave')) {
                $table->boolean('is_medical_leave')->default(false)->after('is_excused');
            }
            if (!Schema::hasColumn('attendance', 'excuse_notes')) {
                $table->text('excuse_notes')->nullable()->after('is_medical_leave');
            }
            if (!Schema::hasColumn('attendance', 'excuse_document_path')) {
                $table->string('excuse_document_path')->nullable()->after('excuse_notes');
            }
            
            // Subject-wise attendance (optional)
            if (!Schema::hasColumn('attendance', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('excuse_document_path');
            }
            
            // Period tracking (for schools with multiple periods per day)
            if (!Schema::hasColumn('attendance', 'period_number')) {
                $table->integer('period_number')->nullable()->after('subject_id');
            }
            if (!Schema::hasColumn('attendance', 'period_name')) {
                $table->string('period_name')->nullable()->after('period_number');
            }
            
            // Marked by tracking
            if (!Schema::hasColumn('attendance', 'marked_by')) {
                $table->unsignedBigInteger('marked_by')->nullable()->after('period_name');
            }
            if (!Schema::hasColumn('attendance', 'marked_at')) {
                $table->timestamp('marked_at')->nullable()->after('marked_by');
            }
            
            // Consecutive absence tracking
            if (!Schema::hasColumn('attendance', 'consecutive_absence_count')) {
                $table->integer('consecutive_absence_count')->default(0)->after('marked_at');
            }
        });

        // Add foreign keys separately to avoid issues
        Schema::table('attendance', function (Blueprint $table) {
            if (Schema::hasTable('attendance_reason_codes') && Schema::hasColumn('attendance', 'reason_code_id')) {
                if (!$this->foreignKeyExists('attendance', 'attendance_reason_code_id_foreign')) {
                    $table->foreign('reason_code_id')->references('id')->on('attendance_reason_codes')->onDelete('set null');
                }
            }
            if (Schema::hasTable('subjects') && Schema::hasColumn('attendance', 'subject_id')) {
                if (!$this->foreignKeyExists('attendance', 'attendance_subject_id_foreign')) {
                    $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');
                }
            }
            if (Schema::hasColumn('attendance', 'marked_by')) {
                if (!$this->foreignKeyExists('attendance', 'attendance_marked_by_foreign')) {
                    $table->foreign('marked_by')->references('id')->on('users')->onDelete('set null');
                }
            }
        });

        // Add indexes
        Schema::table('attendance', function (Blueprint $table) {
            if (Schema::hasColumn('attendance', 'student_id') && Schema::hasColumn('attendance', 'date')) {
                if (!$this->indexExists('attendance', 'idx_attendance_student_date')) {
                    $table->index(['student_id', 'date'], 'idx_attendance_student_date');
                }
            }
            if (Schema::hasColumn('attendance', 'date') && Schema::hasColumn('attendance', 'status')) {
                if (!$this->indexExists('attendance', 'idx_attendance_date_status')) {
                    $table->index(['date', 'status'], 'idx_attendance_date_status');
                }
            }
            if (Schema::hasColumn('attendance', 'reason_code_id')) {
                if (!$this->indexExists('attendance', 'idx_attendance_reason')) {
                    $table->index('reason_code_id', 'idx_attendance_reason');
                }
            }
            if (Schema::hasColumn('attendance', 'is_excused')) {
                if (!$this->indexExists('attendance', 'idx_attendance_excused')) {
                    $table->index('is_excused', 'idx_attendance_excused');
                }
            }
            if (Schema::hasColumn('attendance', 'consecutive_absence_count')) {
                if (!$this->indexExists('attendance', 'idx_attendance_consecutive')) {
                    $table->index('consecutive_absence_count', 'idx_attendance_consecutive');
                }
            }
        });
    }

    private function foreignKeyExists($table, $keyName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $result = $connection->select(
            "SELECT CONSTRAINT_NAME 
             FROM information_schema.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA = ? 
             AND TABLE_NAME = ? 
             AND CONSTRAINT_NAME = ?",
            [$database, $table, $keyName]
        );
        return count($result) > 0;
    }

    private function indexExists($table, $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $result = $connection->select(
            "SELECT INDEX_NAME 
             FROM information_schema.STATISTICS 
             WHERE TABLE_SCHEMA = ? 
             AND TABLE_NAME = ? 
             AND INDEX_NAME = ?",
            [$database, $table, $indexName]
        );
        return count($result) > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign(['reason_code_id']);
            $table->dropForeign(['subject_id']);
            $table->dropForeign(['marked_by']);
            $table->dropIndex('idx_attendance_student_date');
            $table->dropIndex('idx_attendance_date_status');
            $table->dropIndex('idx_attendance_reason');
            $table->dropIndex('idx_attendance_excused');
            $table->dropIndex('idx_attendance_consecutive');
            
            $table->dropColumn([
                'arrival_time', 'departure_time', 'reason_code_id', 'is_excused',
                'is_medical_leave', 'excuse_notes', 'excuse_document_path',
                'subject_id', 'period_number', 'period_name', 'marked_by',
                'marked_at', 'consecutive_absence_count'
            ]);
        });
    }
};
