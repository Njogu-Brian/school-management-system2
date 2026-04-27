<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_payment_plan_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_payment_plan_id')->constrained('fee_payment_plans')->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['fee_payment_plan_id', 'invoice_id'], 'fppi_plan_invoice_unique');
            $table->index(['invoice_id', 'fee_payment_plan_id'], 'fppi_invoice_plan_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payment_plan_invoices');
    }
};

