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
        Schema::create('trip_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->foreignId('drop_off_point_id')->constrained('drop_off_points')->onDelete('cascade');
            $table->integer('sequence_order'); // Order of stops in the trip
            $table->time('estimated_time'); // Estimated arrival/departure time at this stop
            $table->timestamps();
            
            // Ensure unique sequence per trip
            $table->unique(['trip_id', 'sequence_order'], 'unique_trip_sequence');
            $table->index('trip_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_stops');
    }
};
