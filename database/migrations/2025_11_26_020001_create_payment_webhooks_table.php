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
        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->enum('gateway', ['mpesa', 'stripe', 'paypal']);
            $table->string('event_type'); // payment.completed, payment.failed, etc.
            $table->string('event_id')->unique(); // Gateway event ID for idempotency
            $table->json('payload');
            $table->string('signature')->nullable(); // Webhook signature
            $table->boolean('processed')->default(false);
            $table->text('processing_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index('gateway');
            $table->index('event_type');
            $table->index('event_id');
            $table->index('processed');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhooks');
    }
};

