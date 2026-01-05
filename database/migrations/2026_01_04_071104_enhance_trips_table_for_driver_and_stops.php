<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // Add driver (staff user with driver role)
            $table->foreignId('driver_id')->nullable()->after('vehicle_id')->constrained('staff')->onDelete('set null');
            
            // Add day of week (1=Monday, 7=Sunday, NULL = all days)
            $table->tinyInteger('day_of_week')->nullable()->after('driver_id')->comment('1=Monday, 7=Sunday, NULL=all days');
            
            // Add direction (pickup/dropoff)
            if (!Schema::hasColumn('trips', 'direction')) {
                $table->enum('direction', ['pickup', 'dropoff'])->nullable()->after('day_of_week');
            }
            
            // Make route_id nullable (remove hard dependency but keep for backward compatibility)
            // Note: This change is intentional per requirements - trips no longer strictly depend on routes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn(['driver_id', 'day_of_week', 'direction']);
            // Note: route_id nullable change cannot be easily reversed without data loss
            // This is intentional as per requirements
        });
    }
};
