<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'balance_before')) {
                $table->decimal('balance_before', 12, 2)->nullable()->after('unallocated_amount');
            }
            if (! Schema::hasColumn('payments', 'balance_after')) {
                $table->decimal('balance_after', 12, 2)->nullable()->after('balance_before');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'balance_after')) {
                $table->dropColumn('balance_after');
            }
            if (Schema::hasColumn('payments', 'balance_before')) {
                $table->dropColumn('balance_before');
            }
        });
    }
};
