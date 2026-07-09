<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_periods', 'statutory_ruleset_id')) {
                $table->foreignId('statutory_ruleset_id')
                    ->nullable()
                    ->after('pay_date')
                    ->constrained('statutory_rulesets')
                    ->nullOnDelete();
                $table->index('statutory_ruleset_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_periods', 'statutory_ruleset_id')) {
                $table->dropConstrainedForeignId('statutory_ruleset_id');
            }
        });
    }
};

