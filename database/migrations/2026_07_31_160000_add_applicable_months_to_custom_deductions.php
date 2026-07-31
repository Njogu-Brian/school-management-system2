<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('custom_deductions')) {
            return;
        }

        Schema::table('custom_deductions', function (Blueprint $table) {
            if (! Schema::hasColumn('custom_deductions', 'applicable_months')) {
                $table->json('applicable_months')->nullable()->after('frequency');
            }
        });

        DB::statement("ALTER TABLE custom_deductions MODIFY COLUMN frequency ENUM('one_time', 'monthly', 'quarterly', 'yearly', 'custom_months') DEFAULT 'monthly'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('custom_deductions')) {
            return;
        }

        DB::table('custom_deductions')
            ->where('frequency', 'custom_months')
            ->update(['frequency' => 'monthly']);

        DB::statement("ALTER TABLE custom_deductions MODIFY COLUMN frequency ENUM('one_time', 'monthly', 'quarterly', 'yearly') DEFAULT 'monthly'");

        Schema::table('custom_deductions', function (Blueprint $table) {
            if (Schema::hasColumn('custom_deductions', 'applicable_months')) {
                $table->dropColumn('applicable_months');
            }
        });
    }
};
