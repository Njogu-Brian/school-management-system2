<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * M-PESA sends hashed phone numbers (SHA-256, 64 characters) in production
     * for privacy. The original column size of 20 is insufficient.
     */
    public function up(): void
    {
        Schema::table('mpesa_c2b_transactions', function (Blueprint $table) {
            $table->string('msisdn', 100)->change(); // Increased from 20 to 100 to accommodate hashed phone numbers
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mpesa_c2b_transactions', function (Blueprint $table) {
            $table->string('msisdn', 20)->change(); // Revert to original size
        });
    }
};
