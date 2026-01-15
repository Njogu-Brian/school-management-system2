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
        Schema::create('swimming_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2)->comment('Balance after this transaction');
            $table->string('source')->comment('transaction, optional_fee, adjustment, attendance');
            $table->foreignId('source_id')->nullable()->comment('ID of source record (payment_id, optional_fee_id, attendance_id, etc)');
            $table->string('source_type')->nullable()->comment('Model class name for polymorphic relation');
            $table->foreignId('swimming_attendance_id')->nullable()->constrained('swimming_attendance')->onDelete('set null');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['student_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('swimming_attendance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swimming_ledger');
    }
};
