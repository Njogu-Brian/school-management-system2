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
        Schema::create('driver_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('staff')->onDelete('cascade'); // Staff member (driver)
            $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('cascade');
            $table->enum('request_type', ['reassignment', 'dropoff_change', 'pickup_change'])->default('reassignment');
            $table->foreignId('requested_trip_id')->nullable()->constrained('trips')->onDelete('set null');
            $table->foreignId('requested_drop_off_point_id')->nullable()->constrained('drop_off_points')->onDelete('set null');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            
            $table->index(['driver_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_change_requests');
    }
};
