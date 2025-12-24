<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Core student archive markers
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (!Schema::hasColumn('students', 'archived_at')) {
                    $table->timestamp('archived_at')->nullable()->after('archive')->index();
                }
            });
        }

        // Tables that should soft-delete with students
        $softDeleteTables = [
            'attendance',
            'invoices',
            'invoice_items',
            'payments',
            'credit_notes',
            'debit_notes',
            'homework_diary',
            'exam_marks',
            'student_behaviours',
            'student_academic_histories',
            'student_assignments',
            'student_extracurricular_activities',
            'student_medical_records',
            'student_diaries',
            'transport_assignments',
            'report_cards',
            'report_card_results',
        ];

        foreach ($softDeleteTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'archived_at')) {
                        $table->timestamp('archived_at')->nullable()->index();
                    }
                    if (!Schema::hasColumn($tableName, 'deleted_at')) {
                        $table->softDeletes();
                    }
                });
            }
        }

        // Audit log for archive/restore actions
        if (!Schema::hasTable('archive_audits')) {
            Schema::create('archive_audits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->string('action', 20); // archive | restore
                $table->string('reason')->nullable();
                $table->json('counts')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'archived_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('archived_at');
            });
        }

        $softDeleteTables = [
            'attendance',
            'invoices',
            'invoice_items',
            'payments',
            'credit_notes',
            'debit_notes',
            'homework_diary',
            'exam_marks',
            'student_behaviours',
            'student_academic_histories',
            'student_assignments',
            'student_extracurricular_activities',
            'student_medical_records',
            'student_diaries',
            'transport_assignments',
            'report_cards',
            'report_card_results',
        ];

        foreach ($softDeleteTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'archived_at')) {
                        $table->dropColumn('archived_at');
                    }
                    if (Schema::hasColumn($tableName, 'deleted_at')) {
                        $table->dropSoftDeletes();
                    }
                });
            }
        }

        Schema::dropIfExists('archive_audits');
    }
};

