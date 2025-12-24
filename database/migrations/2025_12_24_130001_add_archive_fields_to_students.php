<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (!Schema::hasColumn('students', 'archived_reason')) {
                    $table->string('archived_reason')->nullable()->after('archived_at');
                }
                if (!Schema::hasColumn('students', 'archived_notes')) {
                    $table->text('archived_notes')->nullable()->after('archived_reason');
                }
                if (!Schema::hasColumn('students', 'archived_by')) {
                    $table->unsignedBigInteger('archived_by')->nullable()->after('archived_notes')->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'archived_reason')) {
                    $table->dropColumn('archived_reason');
                }
                if (Schema::hasColumn('students', 'archived_notes')) {
                    $table->dropColumn('archived_notes');
                }
                if (Schema::hasColumn('students', 'archived_by')) {
                    $table->dropColumn('archived_by');
                }
            });
        }
    }
};

