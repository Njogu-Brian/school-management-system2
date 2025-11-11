<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'hire_date')) {
                $table->date('hire_date')->nullable()->after('status');
            }
            if (!Schema::hasColumn('staff', 'termination_date')) {
                $table->date('termination_date')->nullable()->after('hire_date');
            }
            if (!Schema::hasColumn('staff', 'employment_status')) {
                $table->enum('employment_status', ['active', 'on_leave', 'terminated', 'suspended'])->default('active')->after('termination_date');
            }
            if (!Schema::hasColumn('staff', 'employment_type')) {
                $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time')->after('employment_status');
            }
            if (!Schema::hasColumn('staff', 'contract_start_date')) {
                $table->date('contract_start_date')->nullable()->after('employment_type');
            }
            if (!Schema::hasColumn('staff', 'contract_end_date')) {
                $table->date('contract_end_date')->nullable()->after('contract_start_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'contract_end_date')) {
                $table->dropColumn('contract_end_date');
            }
            if (Schema::hasColumn('staff', 'contract_start_date')) {
                $table->dropColumn('contract_start_date');
            }
            if (Schema::hasColumn('staff', 'employment_type')) {
                $table->dropColumn('employment_type');
            }
            if (Schema::hasColumn('staff', 'employment_status')) {
                $table->dropColumn('employment_status');
            }
            if (Schema::hasColumn('staff', 'termination_date')) {
                $table->dropColumn('termination_date');
            }
            if (Schema::hasColumn('staff', 'hire_date')) {
                $table->dropColumn('hire_date');
            }
        });
    }
};
