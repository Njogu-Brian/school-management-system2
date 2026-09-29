<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_transport_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('kind', 32); // morning_pickup | evening_dropoff
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();

            $table->unique(['student_id', 'kind'], 'unique_student_transport_stop_kind');
        });

        Schema::create('student_transport_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('trip_run_id')->nullable()->constrained('trip_runs')->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->string('kind', 32); // morning_pickup | evening_dropoff
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamp('recorded_at');
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'kind', 'recorded_at']);
            $table->index(['trip_run_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transport_events');
        Schema::dropIfExists('student_transport_stops');
    }
};
