<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('purpose')->nullable();
            $table->text('description')->nullable();
            $table->date('advance_date');
            $table->enum('repayment_method', ['lump_sum', 'installments', 'monthly_deduction'])->default('monthly_deduction');
            $table->integer('installment_count')->nullable(); // For installments
            $table->decimal('monthly_deduction_amount', 12, 2)->nullable();
            $table->decimal('amount_repaid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2); // Calculated: amount - amount_repaid
            $table->enum('status', ['pending', 'approved', 'active', 'completed', 'cancelled'])->default('pending');
            $table->date('expected_completion_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('staff_id');
            $table->index('status');
            $table->index('advance_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_advances');
    }
};
