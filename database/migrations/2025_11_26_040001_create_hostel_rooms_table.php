<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_id')->constrained('hostels')->cascadeOnDelete();
            $table->string('room_number');
            $table->enum('room_type', ['single', 'double', 'triple', 'dormitory'])->default('dormitory');
            $table->integer('capacity')->default(1);
            $table->integer('current_occupancy')->default(0);
            $table->integer('floor')->nullable();
            $table->enum('status', ['available', 'occupied', 'maintenance', 'closed'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['hostel_id', 'room_number']);
            $table->index('hostel_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_rooms');
    }
};

