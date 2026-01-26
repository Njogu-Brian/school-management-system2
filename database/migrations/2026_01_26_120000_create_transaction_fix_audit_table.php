<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_fix_audit', function (Blueprint $table) {
            $table->id();
            $table->string('fix_type'); // e.g., 'reset_reversed_payment', 'link_payment', 'fix_swimming'
            $table->string('entity_type'); // 'bank_statement_transaction', 'mpesa_c2b_transaction', 'payment'
            $table->unsignedBigInteger('entity_id');
            $table->json('old_values')->nullable(); // Store old state for reversal
            $table->json('new_values')->nullable(); // Store new state
            $table->text('reason')->nullable();
            $table->boolean('applied')->default(false);
            $table->boolean('reversed')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('applied_by')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamps();
            
            $table->index(['fix_type', 'entity_type', 'entity_id']);
            $table->index('applied');
            $table->index('reversed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_fix_audit');
    }
};
