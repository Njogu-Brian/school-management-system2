<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_vehicle', function (Blueprint $table) {
            // Ensure route_id and vehicle_id are indexed for performance
            $table->index(['route_id', 'vehicle_id']);

            // Optionally add timestamps if needed
            if (!Schema::hasColumn('route_vehicle', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('route_vehicle', function (Blueprint $table) {
            $table->dropIndex(['route_id', 'vehicle_id']);
            $table->dropTimestamps();
        });
    }
};
