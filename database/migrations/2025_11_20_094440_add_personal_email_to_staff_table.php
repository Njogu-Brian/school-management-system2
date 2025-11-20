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
            if (!Schema::hasColumn('staff', 'personal_email')) {
                $table->string('personal_email')->nullable()->after('work_email');
            }
            if (!Schema::hasColumn('staff', 'emergency_contact_relationship')) {
                $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'personal_email')) {
                $table->dropColumn('personal_email');
            }
            if (Schema::hasColumn('staff', 'emergency_contact_relationship')) {
                $table->dropColumn('emergency_contact_relationship');
            }
        });
    }
};
