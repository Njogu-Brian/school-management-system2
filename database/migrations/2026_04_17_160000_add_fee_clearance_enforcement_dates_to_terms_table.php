<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            if (!Schema::hasColumn('terms', 'fee_clearance_day1_date')) {
                $table->date('fee_clearance_day1_date')->nullable()->after('opening_date');
            }
            if (!Schema::hasColumn('terms', 'fee_clearance_strict_from_date')) {
                $table->date('fee_clearance_strict_from_date')->nullable()->after('fee_clearance_day1_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            if (Schema::hasColumn('terms', 'fee_clearance_strict_from_date')) {
                $table->dropColumn('fee_clearance_strict_from_date');
            }
            if (Schema::hasColumn('terms', 'fee_clearance_day1_date')) {
                $table->dropColumn('fee_clearance_day1_date');
            }
        });
    }
};

