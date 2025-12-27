<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'has_allergies')) {
                $table->boolean('has_allergies')->default(false)->after('special_needs_description');
            }
            if (!Schema::hasColumn('students', 'allergies_notes')) {
                $table->text('allergies_notes')->nullable()->after('has_allergies');
            }
            if (!Schema::hasColumn('students', 'is_fully_immunized')) {
                $table->boolean('is_fully_immunized')->default(false)->after('allergies_notes');
            }
            if (!Schema::hasColumn('students', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('emergency_medical_contact_phone');
            }
            if (!Schema::hasColumn('students', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            }
            if (!Schema::hasColumn('students', 'emergency_contact_country_code')) {
                $table->string('emergency_contact_country_code', 8)->nullable()->after('emergency_contact_phone');
            }
            if (!Schema::hasColumn('students', 'residential_area')) {
                $table->string('residential_area')->nullable()->after('home_postal_code');
            }
            if (!Schema::hasColumn('students', 'preferred_hospital')) {
                $table->string('preferred_hospital')->nullable()->after('residential_area');
            }
        });

        Schema::table('parent_info', function (Blueprint $table) {
            if (!Schema::hasColumn('parent_info', 'father_phone_country_code')) {
                $table->string('father_phone_country_code', 8)->nullable()->after('father_phone');
            }
            if (!Schema::hasColumn('parent_info', 'mother_phone_country_code')) {
                $table->string('mother_phone_country_code', 8)->nullable()->after('mother_phone');
            }
            if (!Schema::hasColumn('parent_info', 'guardian_phone_country_code')) {
                $table->string('guardian_phone_country_code', 8)->nullable()->after('guardian_phone');
            }
            if (!Schema::hasColumn('parent_info', 'father_id_document')) {
                $table->string('father_id_document')->nullable()->after('father_id_number');
            }
            if (!Schema::hasColumn('parent_info', 'mother_id_document')) {
                $table->string('mother_id_document')->nullable()->after('mother_id_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'has_allergies',
                'allergies_notes',
                'is_fully_immunized',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_country_code',
                'residential_area',
                'preferred_hospital',
            ]);
        });

        Schema::table('parent_info', function (Blueprint $table) {
            $table->dropColumn([
                'father_phone_country_code',
                'mother_phone_country_code',
                'guardian_phone_country_code',
                'father_id_document',
                'mother_id_document',
            ]);
        });
    }
};

