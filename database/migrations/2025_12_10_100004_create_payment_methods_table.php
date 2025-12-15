<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Cash", "M-Pesa", "Cheque"
            $table->string('code')->unique(); // e.g., "CASH", "MPESA", "CHEQUE"
            $table->boolean('requires_reference')->default(false);
            $table->boolean('is_online')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('display_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};

