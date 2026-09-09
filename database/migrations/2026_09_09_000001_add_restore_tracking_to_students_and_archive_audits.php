<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track the most recent restoration on the student record itself
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'restored_at')) {
                $table->timestamp('restored_at')->nullable()->after('archived_by');
            }
            if (! Schema::hasColumn('students', 'restored_reason')) {
                $table->string('restored_reason')->nullable()->after('restored_at');
            }
            if (! Schema::hasColumn('students', 'restored_by')) {
                $table->unsignedBigInteger('restored_by')->nullable()->after('restored_reason');
            }
        });

        // Optional free-text notes on archive/restore audit events
        Schema::table('archive_audits', function (Blueprint $table) {
            if (! Schema::hasColumn('archive_audits', 'notes')) {
                $table->text('notes')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            foreach (['restored_at', 'restored_reason', 'restored_by'] as $col) {
                if (Schema::hasColumn('students', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('archive_audits', function (Blueprint $table) {
            if (Schema::hasColumn('archive_audits', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
