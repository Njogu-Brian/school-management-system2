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
            if (Schema::hasColumn('attendance', 'arrival_time')) {
                $table->dropColumn('arrival_time');
            }
            if (Schema::hasColumn('attendance', 'departure_time')) {
                $table->dropColumn('departure_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->time('arrival_time')->nullable()->after('date');
            $table->time('departure_time')->nullable()->after('arrival_time');
        });
    }
};
