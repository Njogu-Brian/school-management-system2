<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exams') && Schema::hasColumn('exams', 'type')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exams') && !Schema::hasColumn('exams', 'type')) {
            Schema::table('exams', function (Blueprint $table) {
                // restore as string to avoid enum-alter issues across DBs
                $table->string('type')->nullable()->after('name');
            });
        }
    }
};

