<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('communication_logs', 'title')) {
                $table->string('title')->nullable()->after('channel');
            }
        });
    }

    public function down(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            if (Schema::hasColumn('communication_logs', 'title')) {
                $table->dropColumn('title');
            }
        });
    }
};


