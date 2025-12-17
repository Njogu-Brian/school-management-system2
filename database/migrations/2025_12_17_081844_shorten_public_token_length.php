<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, regenerate all existing tokens to be 10 chars
        $payments = DB::table('payments')
            ->whereNotNull('public_token')
            ->whereRaw('LENGTH(public_token) > 10')
            ->get();
        
        foreach ($payments as $payment) {
            do {
                $token = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 0, 10);
            } while (DB::table('payments')->where('public_token', $token)->exists());
            
            DB::table('payments')
                ->where('id', $payment->id)
                ->update(['public_token' => $token]);
        }
        
        // Now update column definition
        DB::statement('ALTER TABLE payments MODIFY COLUMN public_token VARCHAR(10) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE payments MODIFY COLUMN public_token VARCHAR(64) NULL');
    }
};
