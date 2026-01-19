<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ensures all fields required by FamilyUpdateController exist in the database.
     */
    public function up(): void
    {
        // Ensure parent_info table has all required fields
        Schema::table('parent_info', function (Blueprint $table) {
            // Country codes for phone numbers
            if (!Schema::hasColumn('parent_info', 'father_phone_country_code')) {
                $table->string('father_phone_country_code', 10)->nullable()->after('father_phone');
            }
            if (!Schema::hasColumn('parent_info', 'mother_phone_country_code')) {
                $table->string('mother_phone_country_code', 10)->nullable()->after('mother_phone');
            }
            if (!Schema::hasColumn('parent_info', 'guardian_phone_country_code')) {
                $table->string('guardian_phone_country_code', 10)->nullable()->after('guardian_phone');
            }
            
            // Country codes for WhatsApp numbers
            if (!Schema::hasColumn('parent_info', 'father_whatsapp_country_code')) {
                $table->string('father_whatsapp_country_code', 10)->nullable()->after('father_phone_country_code');
            }
            if (!Schema::hasColumn('parent_info', 'mother_whatsapp_country_code')) {
                $table->string('mother_whatsapp_country_code', 10)->nullable()->after('mother_phone_country_code');
            }
            
            // WhatsApp numbers
            if (!Schema::hasColumn('parent_info', 'father_whatsapp')) {
                $table->string('father_whatsapp')->nullable()->after('father_email');
            }
            if (!Schema::hasColumn('parent_info', 'mother_whatsapp')) {
                $table->string('mother_whatsapp')->nullable()->after('mother_email');
            }
            
            // Guardian relationship
            if (!Schema::hasColumn('parent_info', 'guardian_relationship')) {
                $table->string('guardian_relationship')->nullable()->after('guardian_email');
            }
            
            // Marital status
            if (!Schema::hasColumn('parent_info', 'marital_status')) {
                $table->string('marital_status')->nullable()->after('guardian_relationship');
            }
            
            // ID documents
            if (!Schema::hasColumn('parent_info', 'father_id_document')) {
                $table->string('father_id_document')->nullable()->after('father_id_number');
            }
            if (!Schema::hasColumn('parent_info', 'mother_id_document')) {
                $table->string('mother_id_document')->nullable()->after('mother_id_number');
            }
        });

        // Ensure students table has all required fields
        Schema::table('students', function (Blueprint $table) {
            // Health information
            if (!Schema::hasColumn('students', 'has_allergies')) {
                $table->boolean('has_allergies')->default(false)->nullable();
            }
            if (!Schema::hasColumn('students', 'allergies_notes')) {
                $table->text('allergies_notes')->nullable();
            }
            if (!Schema::hasColumn('students', 'is_fully_immunized')) {
                $table->boolean('is_fully_immunized')->default(false)->nullable();
            }
            
            // Emergency contact information
            if (!Schema::hasColumn('students', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable();
            }
            if (!Schema::hasColumn('students', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone')->nullable();
            }
            
            // Location and medical preferences
            if (!Schema::hasColumn('students', 'residential_area')) {
                $table->string('residential_area')->nullable();
            }
            if (!Schema::hasColumn('students', 'preferred_hospital')) {
                $table->string('preferred_hospital')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We don't drop columns in down() to avoid data loss
        // If you need to rollback, create a separate migration
    }
};
