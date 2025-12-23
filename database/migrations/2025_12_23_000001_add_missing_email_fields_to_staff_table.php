<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'work_email')) {
                $table->string('work_email')->nullable()->after('last_name');
            }

            if (!Schema::hasColumn('staff', 'personal_email')) {
                $table->string('personal_email')->nullable()->after('work_email');
            }

            if (!Schema::hasColumn('staff', 'residential_address')) {
                $table->string('residential_address')->nullable()->after('marital_status');
            }

            if (!Schema::hasColumn('staff', 'emergency_contact_relationship')) {
                $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_name');
            }
        });

        // Backfill from legacy columns where possible
        if (Schema::hasColumn('staff', 'email')) {
            DB::table('staff')
                ->whereNull('work_email')
                ->whereNotNull('email')
                ->update(['work_email' => DB::raw('email')]);
        }

        if (Schema::hasColumn('staff', 'address')) {
            DB::table('staff')
                ->whereNull('residential_address')
                ->whereNotNull('address')
                ->update(['residential_address' => DB::raw('address')]);
        }
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'emergency_contact_relationship')) {
                $table->dropColumn('emergency_contact_relationship');
            }

            if (Schema::hasColumn('staff', 'residential_address')) {
                $table->dropColumn('residential_address');
            }

            if (Schema::hasColumn('staff', 'personal_email')) {
                $table->dropColumn('personal_email');
            }

            if (Schema::hasColumn('staff', 'work_email')) {
                $table->dropColumn('work_email');
            }
        });
    }
};

