<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_transactions')) {
            return;
        }

        $after = Schema::hasColumn('payment_transactions', 'admin_notes')
            ? 'admin_notes'
            : 'failure_reason';

        Schema::table('payment_transactions', function (Blueprint $table) use ($after) {
            if (! Schema::hasColumn('payment_transactions', 'is_shared')) {
                $table->boolean('is_shared')->default(false)->after($after);
            }
            if (! Schema::hasColumn('payment_transactions', 'shared_allocations')) {
                $table->json('shared_allocations')->nullable()->after('is_shared'); // [['student_id' => 1, 'amount' => 5000], ...]
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn(['is_shared', 'shared_allocations']);
        });
    }
};
