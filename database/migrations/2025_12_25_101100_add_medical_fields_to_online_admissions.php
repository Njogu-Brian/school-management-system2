<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('online_admissions', function (Blueprint $table) {
            if (Schema::hasColumn('online_admissions', 'nemis_number')) {
                // keep column but allow nullable (already is) - UI will hide it
            }
            if (Schema::hasColumn('online_admissions', 'knec_assessment_number')) {
                // keep column but allow nullable (already is) - UI will hide it
            }
            if (!Schema::hasColumn('online_admissions', 'has_allergies')) {
                $table->boolean('has_allergies')->default(false)->after('knec_assessment_number');
            }
            if (!Schema::hasColumn('online_admissions', 'allergies_notes')) {
                $table->text('allergies_notes')->nullable()->after('has_allergies');
            }
            if (!Schema::hasColumn('online_admissions', 'is_fully_immunized')) {
                $table->boolean('is_fully_immunized')->default(false)->after('allergies_notes');
            }
            if (!Schema::hasColumn('online_admissions', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('guardian_email');
            }
            if (!Schema::hasColumn('online_admissions', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            }
            if (!Schema::hasColumn('online_admissions', 'emergency_contact_country_code')) {
                $table->string('emergency_contact_country_code', 8)->nullable()->after('emergency_contact_phone');
            }
            if (!Schema::hasColumn('online_admissions', 'residential_area')) {
                $table->string('residential_area')->nullable()->after('guardian_id_number');
            }
            if (!Schema::hasColumn('online_admissions', 'preferred_hospital')) {
                $table->string('preferred_hospital')->nullable()->after('residential_area');
            }
            if (!Schema::hasColumn('online_admissions', 'father_phone_country_code')) {
                $table->string('father_phone_country_code', 8)->nullable()->after('father_phone');
            }
            if (!Schema::hasColumn('online_admissions', 'mother_phone_country_code')) {
                $table->string('mother_phone_country_code', 8)->nullable()->after('mother_phone');
            }
            if (!Schema::hasColumn('online_admissions', 'guardian_phone_country_code')) {
                $table->string('guardian_phone_country_code', 8)->nullable()->after('guardian_phone');
            }
            if (!Schema::hasColumn('online_admissions', 'father_id_document')) {
                $table->string('father_id_document')->nullable()->after('father_id_number');
            }
            if (!Schema::hasColumn('online_admissions', 'mother_id_document')) {
                $table->string('mother_id_document')->nullable()->after('mother_id_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('online_admissions', function (Blueprint $table) {
            $table->dropColumn([
                'has_allergies',
                'allergies_notes',
                'is_fully_immunized',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_country_code',
                'residential_area',
                'preferred_hospital',
                'father_phone_country_code',
                'mother_phone_country_code',
                'guardian_phone_country_code',
                'father_id_document',
                'mother_id_document',
            ]);
        });
    }
};

