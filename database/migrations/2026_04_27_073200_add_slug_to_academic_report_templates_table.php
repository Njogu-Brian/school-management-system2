<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academic_report_templates')) {
            return;
        }
        Schema::table('academic_report_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('academic_report_templates', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('academic_report_templates')) {
            return;
        }
        Schema::table('academic_report_templates', function (Blueprint $table) {
            if (Schema::hasColumn('academic_report_templates', 'slug')) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            }
        });
    }
};

