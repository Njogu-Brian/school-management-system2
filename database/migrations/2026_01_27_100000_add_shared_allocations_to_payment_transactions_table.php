<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->boolean('is_shared')->default(false)->after('admin_notes');
            $table->json('shared_allocations')->nullable()->after('is_shared'); // [['student_id' => 1, 'amount' => 5000], ...]
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn(['is_shared', 'shared_allocations']);
        });
    }
};
