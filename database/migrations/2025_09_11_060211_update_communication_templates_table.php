<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('communication_templates', 'code')) {
                $table->string('code')->unique()->after('id');
            }
            if (!Schema::hasColumn('communication_templates', 'subject')) {
                $table->string('subject')->nullable()->after('title');
            }
            if (!Schema::hasColumn('communication_templates', 'attachment')) {
                $table->string('attachment')->nullable()->after('subject');
            }
        });
    }

    public function down(): void
    {
        Schema::table('communication_templates', function (Blueprint $table) {
            if (Schema::hasColumn('communication_templates', 'code')) {
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('communication_templates', 'subject')) {
                $table->dropColumn('subject');
            }
            if (Schema::hasColumn('communication_templates', 'attachment')) {
                $table->dropColumn('attachment');
            }
        });
    }
};
