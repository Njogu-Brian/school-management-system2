<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->date('run_date');
            $table->foreignId('driver_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('status', 32)->default('scheduled'); // scheduled|in_progress|completed|cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->decimal('last_accuracy_meters', 8, 2)->nullable();
            $table->decimal('last_speed_kmh', 8, 2)->nullable();
            $table->timestamp('last_location_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['trip_id', 'run_date'], 'unique_trip_run_date');
            $table->index(['status', 'run_date']);
            $table->index(['driver_id', 'run_date']);
        });

        Schema::create('trip_run_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_run_id')->constrained('trip_runs')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('speed_kmh', 8, 2)->nullable();
            $table->decimal('heading', 6, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['trip_run_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_run_locations');
        Schema::dropIfExists('trip_runs');
    }
};
