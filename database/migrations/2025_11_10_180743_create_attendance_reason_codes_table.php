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
        Schema::create('attendance_reason_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('requires_excuse')->default(false);
            $table->boolean('is_medical')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Insert default reason codes
        DB::table('attendance_reason_codes')->insert([
            ['code' => 'SICK', 'name' => 'Sick', 'description' => 'Student is ill', 'requires_excuse' => true, 'is_medical' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'FAMILY', 'name' => 'Family Emergency', 'description' => 'Family emergency', 'requires_excuse' => true, 'is_medical' => false, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'MEDICAL', 'name' => 'Medical Appointment', 'description' => 'Medical appointment', 'requires_excuse' => true, 'is_medical' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'FUNERAL', 'name' => 'Funeral', 'description' => 'Attending funeral', 'requires_excuse' => true, 'is_medical' => false, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'UNEXCUSED', 'name' => 'Unexcused Absence', 'description' => 'No valid excuse provided', 'requires_excuse' => false, 'is_medical' => false, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'OTHER', 'name' => 'Other', 'description' => 'Other reason', 'requires_excuse' => false, 'is_medical' => false, 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_reason_codes');
    }
};
