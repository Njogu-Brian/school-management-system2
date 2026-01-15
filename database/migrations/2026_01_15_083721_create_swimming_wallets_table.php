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
        Schema::create('swimming_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0)->comment('Current swimming credit balance');
            $table->decimal('total_credited', 10, 2)->default(0)->comment('Total amount ever credited');
            $table->decimal('total_debited', 10, 2)->default(0)->comment('Total amount ever debited');
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamps();
            
            $table->index('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swimming_wallets');
    }
};
