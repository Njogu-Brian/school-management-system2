<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_advances', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_advances', 'requested_amount')) {
                $table->decimal('requested_amount', 12, 2)->nullable()->after('amount');
            }
        });

        // Backfill: requested = issued amount for existing rows.
        if (Schema::hasColumn('staff_advances', 'requested_amount')) {
            DB::table('staff_advances')
                ->whereNull('requested_amount')
                ->update(['requested_amount' => DB::raw('amount')]);
        }
    }

    public function down(): void
    {
        Schema::table('staff_advances', function (Blueprint $table) {
            if (Schema::hasColumn('staff_advances', 'requested_amount')) {
                $table->dropColumn('requested_amount');
            }
        });
    }
};
