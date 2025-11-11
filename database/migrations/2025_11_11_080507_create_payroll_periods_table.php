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
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('period_name'); // e.g., "January 2025"
            $table->year('year');
            $table->tinyInteger('month'); // 1-12
            $table->date('start_date');
            $table->date('end_date');
            $table->date('pay_date'); // When staff will be paid
            
            // Status
            $table->enum('status', ['draft', 'processing', 'completed', 'locked'])->default('draft');
            
            // Totals
            $table->decimal('total_gross', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('total_net', 12, 2)->default(0);
            $table->integer('staff_count')->default(0);
            
            // Processing info
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['year', 'month']);
            $table->index('status');
            $table->index('pay_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
