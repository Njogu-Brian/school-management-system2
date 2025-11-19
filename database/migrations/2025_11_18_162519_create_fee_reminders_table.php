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
        Schema::create('fee_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->enum('channel', ['email', 'sms', 'both'])->default('both');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->decimal('outstanding_amount', 10, 2);
            $table->date('due_date');
            $table->integer('days_before_due')->default(7);
            $table->timestamp('sent_at')->nullable();
            $table->text('message')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_reminders');
    }
};
