<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists (was created manually or in previous migration)
        if (Schema::hasTable('pos_orders')) {
            // Just ensure the payment_transaction_id column exists if it doesn't
            if (!Schema::hasColumn('pos_orders', 'payment_transaction_id')) {
                Schema::table('pos_orders', function (Blueprint $table) {
                    $table->unsignedBigInteger('payment_transaction_id')->nullable()->after('payment_reference');
                    $table->index('payment_transaction_id');
                });
            }
            return;
        }
        
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            // Add columns without foreign key constraints (will be added in separate migration)
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // Staff who processed
            $table->enum('order_type', ['stationery', 'uniform', 'mixed'])->default('stationery');
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'refunded'])->default('pending');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('payment_method')->nullable(); // cash, mpesa, card, etc.
            $table->string('payment_reference')->nullable();
            // Payment transaction FK - conditional, as payment_transactions table may not exist yet
            $table->unsignedBigInteger('payment_transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['parent_id', 'status']);
            $table->index('order_number');
            $table->index('payment_status');
            $table->index('payment_transaction_id');
            $table->index('user_id');
        });
        
        // Note: Foreign key constraints will be added in separate migrations
        // after students, parent_info, users, and payment_transactions tables are created
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_orders');
    }
};



