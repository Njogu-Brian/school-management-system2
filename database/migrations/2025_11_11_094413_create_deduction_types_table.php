<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deduction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "School Uniform", "Welfare Contribution", "Loan"
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            $table->enum('calculation_method', ['fixed_amount', 'percentage_of_basic', 'percentage_of_gross', 'custom'])->default('fixed_amount');
            $table->decimal('default_amount', 12, 2)->nullable(); // For fixed amount
            $table->decimal('percentage', 5, 2)->nullable(); // For percentage-based
            $table->boolean('is_active')->default(true);
            $table->boolean('is_statutory')->default(false); // NSSF, NHIF, PAYE are statutory
            $table->boolean('requires_approval')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deduction_types');
    }
};
