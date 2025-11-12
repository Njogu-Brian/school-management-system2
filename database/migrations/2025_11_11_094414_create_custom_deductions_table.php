<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');
            $table->foreignId('deduction_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_advance_id')->nullable()->constrained()->onDelete('set null'); // If linked to an advance
            $table->decimal('amount', 12, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->enum('frequency', ['one_time', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->integer('installment_number')->nullable(); // For installment-based deductions
            $table->integer('total_installments')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable(); // Total to be deducted over installments
            $table->decimal('amount_deducted', 12, 2)->default(0); // Total already deducted
            $table->enum('status', ['active', 'completed', 'cancelled', 'suspended'])->default('active');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('staff_id');
            $table->index('deduction_type_id');
            $table->index('status');
            $table->index('effective_from');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_deductions');
    }
};
