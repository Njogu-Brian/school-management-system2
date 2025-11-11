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
        Schema::create('staff_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            $table->integer('entitlement_days')->default(0); // Days entitled for this year
            $table->integer('used_days')->default(0); // Days already used
            $table->integer('remaining_days')->default(0); // Calculated: entitlement - used
            $table->integer('carried_forward')->default(0); // Days carried from previous year
            $table->timestamps();

            // Unique constraint: one balance per staff, leave type, and academic year
            $table->unique(['staff_id', 'leave_type_id', 'academic_year_id'], 'unique_staff_leave_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_leave_balances');
    }
};
