<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            if (!Schema::hasColumn('trips', 'type')) {
                $table->string('type', 32)->nullable()->after('trip_name');
            }
        });

        // Backfill from direction for existing trips
        if (Schema::hasColumn('trips', 'type') && Schema::hasColumn('trips', 'direction')) {
            DB::table('trips')->where('direction', 'pickup')->whereNull('type')->update(['type' => 'Morning']);
            DB::table('trips')->where('direction', 'dropoff')->whereNull('type')->update(['type' => 'Evening']);
        }
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            if (Schema::hasColumn('trips', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
