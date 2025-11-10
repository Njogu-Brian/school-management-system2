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
        Schema::table('students', function (Blueprint $table) {
            // Personal Details
            $table->string('national_id_number')->nullable()->after('knec_assessment_number');
            $table->string('passport_number')->nullable()->after('national_id_number');
            $table->string('religion')->nullable()->after('passport_number');
            $table->string('ethnicity')->nullable()->after('religion');
            $table->string('home_address')->nullable()->after('ethnicity');
            $table->string('home_city')->nullable()->after('home_address');
            $table->string('home_county')->nullable()->after('home_city');
            $table->string('home_postal_code')->nullable()->after('home_county');
            $table->string('language_preference')->nullable()->after('home_postal_code');
            
            // Medical Information
            $table->string('blood_group')->nullable()->after('language_preference');
            $table->text('allergies')->nullable()->after('blood_group');
            $table->text('chronic_conditions')->nullable()->after('allergies');
            $table->string('medical_insurance_provider')->nullable()->after('chronic_conditions');
            $table->string('medical_insurance_number')->nullable()->after('medical_insurance_provider');
            $table->string('emergency_medical_contact_name')->nullable()->after('medical_insurance_number');
            $table->string('emergency_medical_contact_phone')->nullable()->after('emergency_medical_contact_name');
            
            // Previous School Information
            $table->text('previous_schools')->nullable()->after('emergency_medical_contact_phone'); // JSON format
            $table->text('transfer_reason')->nullable()->after('previous_schools');
            
            // Special Needs
            $table->boolean('has_special_needs')->default(false)->after('transfer_reason');
            $table->text('special_needs_description')->nullable()->after('has_special_needs');
            $table->text('learning_disabilities')->nullable()->after('special_needs_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'national_id_number', 'passport_number', 'religion', 'ethnicity',
                'home_address', 'home_city', 'home_county', 'home_postal_code',
                'language_preference', 'blood_group', 'allergies', 'chronic_conditions',
                'medical_insurance_provider', 'medical_insurance_number',
                'emergency_medical_contact_name', 'emergency_medical_contact_phone',
                'previous_schools', 'transfer_reason', 'has_special_needs',
                'special_needs_description', 'learning_disabilities'
            ]);
        });
    }
};
