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
        Schema::table('parent_info', function (Blueprint $table) {
            // Father extended info
            $table->string('father_occupation')->nullable()->after('father_id_number');
            $table->string('father_employer')->nullable()->after('father_occupation');
            $table->string('father_work_address')->nullable()->after('father_employer');
            $table->string('father_education_level')->nullable()->after('father_work_address');
            $table->string('father_whatsapp')->nullable()->after('father_email');
            
            // Mother extended info
            $table->string('mother_occupation')->nullable()->after('mother_id_number');
            $table->string('mother_employer')->nullable()->after('mother_occupation');
            $table->string('mother_work_address')->nullable()->after('mother_employer');
            $table->string('mother_education_level')->nullable()->after('mother_work_address');
            $table->string('mother_whatsapp')->nullable()->after('mother_email');
            
            // Guardian extended info
            $table->string('guardian_occupation')->nullable()->after('guardian_relationship');
            $table->string('guardian_employer')->nullable()->after('guardian_occupation');
            $table->string('guardian_work_address')->nullable()->after('guardian_employer');
            $table->string('guardian_education_level')->nullable()->after('guardian_work_address');
            $table->string('guardian_whatsapp')->nullable()->after('guardian_email');
            
            // Family information
            $table->string('family_income_bracket')->nullable()->after('guardian_whatsapp'); // for financial aid
            $table->string('primary_contact_person')->nullable()->after('family_income_bracket'); // father, mother, guardian
            $table->string('communication_preference')->nullable()->after('primary_contact_person'); // sms, email, phone, whatsapp
            $table->string('language_preference')->nullable()->after('communication_preference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_info', function (Blueprint $table) {
            $table->dropColumn([
                'father_occupation', 'father_employer', 'father_work_address', 'father_education_level', 'father_whatsapp',
                'mother_occupation', 'mother_employer', 'mother_work_address', 'mother_education_level', 'mother_whatsapp',
                'guardian_occupation', 'guardian_employer', 'guardian_work_address', 'guardian_education_level', 'guardian_whatsapp',
                'family_income_bracket', 'primary_contact_person', 'communication_preference', 'language_preference'
            ]);
        });
    }
};
