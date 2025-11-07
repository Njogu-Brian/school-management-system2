<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // If legacy columns exist, rename them. Guard with checks.
            if (Schema::hasColumn('staff', 'email') && !Schema::hasColumn('staff', 'work_email')) {
                $table->renameColumn('email', 'work_email');
            }
            if (Schema::hasColumn('staff', 'address') && !Schema::hasColumn('staff', 'residential_address')) {
                $table->renameColumn('address', 'residential_address');
            }

            // Add only if missing
            if (!Schema::hasColumn('staff', 'work_email')) {
                $table->string('work_email')->nullable();
            }
            if (!Schema::hasColumn('staff', 'personal_email')) {
                $table->string('personal_email')->nullable();
            }
            if (!Schema::hasColumn('staff', 'residential_address')) {
                $table->string('residential_address')->nullable();
            }
            if (!Schema::hasColumn('staff', 'emergency_contact_relationship')) {
                $table->string('emergency_contact_relationship')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'personal_email')) {
                $table->dropColumn('personal_email');
            }
            if (Schema::hasColumn('staff', 'emergency_contact_relationship')) {
                $table->dropColumn('emergency_contact_relationship');
            }
            if (Schema::hasColumn('staff', 'residential_address') && !Schema::hasColumn('staff', 'address')) {
                $table->renameColumn('residential_address', 'address');
            }
            if (Schema::hasColumn('staff', 'work_email') && !Schema::hasColumn('staff', 'email')) {
                $table->renameColumn('work_email', 'email');
            }
        });
    }
};
