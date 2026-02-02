<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mpesa_c2b_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('mpesa_c2b_transactions', 'is_shared')) {
                $table->boolean('is_shared')->default(false)->after('allocation_status');
            }

            if (!Schema::hasColumn('mpesa_c2b_transactions', 'shared_allocations')) {
                $table->json('shared_allocations')->nullable()->after('is_shared');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_c2b_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('mpesa_c2b_transactions', 'shared_allocations')) {
                $table->dropColumn('shared_allocations');
            }

            if (Schema::hasColumn('mpesa_c2b_transactions', 'is_shared')) {
                $table->dropColumn('is_shared');
            }
        });
    }
};
