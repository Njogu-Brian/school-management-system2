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
        Schema::create('discount_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Template name
            $table->enum('discount_type', ['sibling', 'referral', 'early_repayment', 'transport', 'manual', 'other'])->default('manual');
            $table->enum('type', ['percentage', 'fixed_amount'])->default('percentage');
            $table->enum('frequency', ['termly', 'yearly', 'once', 'manual'])->default('manual');
            $table->enum('scope', ['votehead', 'invoice', 'student', 'family'])->default('votehead');
            $table->decimal('value', 10, 2); // Percentage or fixed amount
            $table->string('reason');
            $table->text('description')->nullable();
            $table->date('end_date')->nullable(); // Optional expiry for template
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_templates');
    }
};
