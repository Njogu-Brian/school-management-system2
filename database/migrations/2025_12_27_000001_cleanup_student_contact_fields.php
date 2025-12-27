<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $drops = [
                'national_id_number',
                'passport_number',
                'ethnicity',
                'language_preference',
                'blood_group',
                'home_address',
                'home_city',
                'home_county',
                'home_postal_code',
                'medical_insurance_provider',
                'medical_insurance_number',
                'emergency_medical_contact_name',
                'emergency_medical_contact_phone',
                'emergency_contact_country_code',
            ];

            foreach ($drops as $col) {
                if (Schema::hasColumn('students', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('online_admissions', function (Blueprint $table) {
            if (Schema::hasColumn('online_admissions', 'emergency_contact_country_code')) {
                $table->dropColumn('emergency_contact_country_code');
            }
            if (!Schema::hasColumn('online_admissions', 'previous_school')) {
                $table->string('previous_school')->nullable()->after('preferred_classroom_id');
            }
            if (!Schema::hasColumn('online_admissions', 'transfer_reason')) {
                $table->string('transfer_reason')->nullable()->after('previous_school');
            }
            if (!Schema::hasColumn('online_admissions', 'marital_status')) {
                // use nullable enum without relying on a specific column order to avoid missing-column errors
                $table->enum('marital_status', ['married','single_parent','co_parenting'])->nullable();
            }
        });

        Schema::table('parent_info', function (Blueprint $table) {
            if (!Schema::hasColumn('parent_info', 'marital_status')) {
                $table->enum('marital_status', ['married','single_parent','co_parenting'])->nullable()->after('guardian_relationship');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'national_id_number')) {
                $table->string('national_id_number')->nullable()->after('knec_assessment_number');
            }
            if (!Schema::hasColumn('students', 'passport_number')) {
                $table->string('passport_number')->nullable()->after('national_id_number');
            }
            if (!Schema::hasColumn('students', 'ethnicity')) {
                $table->string('ethnicity')->nullable()->after('religion');
            }
            if (!Schema::hasColumn('students', 'language_preference')) {
                $table->string('language_preference')->nullable()->after('home_postal_code');
            }
            if (!Schema::hasColumn('students', 'blood_group')) {
                $table->string('blood_group')->nullable()->after('language_preference');
            }
            if (!Schema::hasColumn('students', 'home_address')) {
                $table->string('home_address')->nullable()->after('ethnicity');
            }
            if (!Schema::hasColumn('students', 'home_city')) {
                $table->string('home_city')->nullable()->after('home_address');
            }
            if (!Schema::hasColumn('students', 'home_county')) {
                $table->string('home_county')->nullable()->after('home_city');
            }
            if (!Schema::hasColumn('students', 'home_postal_code')) {
                $table->string('home_postal_code')->nullable()->after('home_county');
            }
            if (!Schema::hasColumn('students', 'medical_insurance_provider')) {
                $table->string('medical_insurance_provider')->nullable()->after('chronic_conditions');
            }
            if (!Schema::hasColumn('students', 'medical_insurance_number')) {
                $table->string('medical_insurance_number')->nullable()->after('medical_insurance_provider');
            }
            if (!Schema::hasColumn('students', 'emergency_medical_contact_name')) {
                $table->string('emergency_medical_contact_name')->nullable()->after('medical_insurance_number');
            }
            if (!Schema::hasColumn('students', 'emergency_medical_contact_phone')) {
                $table->string('emergency_medical_contact_phone')->nullable()->after('emergency_medical_contact_name');
            }
            if (!Schema::hasColumn('students', 'emergency_contact_country_code')) {
                $table->string('emergency_contact_country_code')->nullable()->after('emergency_contact_phone');
            }
        });

        Schema::table('online_admissions', function (Blueprint $table) {
            if (Schema::hasColumn('online_admissions', 'previous_school')) {
                $table->dropColumn('previous_school');
            }
            if (Schema::hasColumn('online_admissions', 'transfer_reason')) {
                $table->dropColumn('transfer_reason');
            }
            if (!Schema::hasColumn('online_admissions', 'emergency_contact_country_code')) {
                $table->string('emergency_contact_country_code')->nullable()->after('emergency_contact_phone');
            }
            if (Schema::hasColumn('online_admissions', 'marital_status')) {
                $table->dropColumn('marital_status');
            }
        });

        Schema::table('parent_info', function (Blueprint $table) {
            if (Schema::hasColumn('parent_info', 'marital_status')) {
                $table->dropColumn('marital_status');
            }
        });
    }
};

