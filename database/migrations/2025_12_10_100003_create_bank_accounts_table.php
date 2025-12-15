<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Account name/identifier
            $table->string('account_number');
            $table->string('bank_name');
            $table->string('branch')->nullable();
            $table->enum('account_type', ['current', 'savings', 'deposit', 'other'])->default('current');
            $table->boolean('is_active')->default(true);
            $table->string('currency', 3)->default('KES');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};

