<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->foreignId('invoice_item_id')->constrained('invoice_items')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamp('allocated_at')->useCurrent();
            $table->foreignId('allocated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('payment_id');
            $table->index('invoice_item_id');
            // Ensure allocation doesn't exceed payment amount (enforced in application logic)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};

