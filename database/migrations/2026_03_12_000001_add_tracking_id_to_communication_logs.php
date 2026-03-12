<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('communication_logs', 'tracking_id')) {
                $table->string('tracking_id', 100)->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            if (Schema::hasColumn('communication_logs', 'tracking_id')) {
                $table->dropColumn('tracking_id');
            }
        });
    }
};
