<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix phone numbers that contain +KE and replace with +254
        DB::statement("UPDATE parent_infos SET 
            father_phone = REPLACE(REPLACE(father_phone, '+KE', '+254'), 'KE+', '+254'),
            mother_phone = REPLACE(REPLACE(mother_phone, '+KE', '+254'), 'KE+', '+254'),
            guardian_phone = REPLACE(REPLACE(guardian_phone, '+KE', '+254'), 'KE+', '+254'),
            father_whatsapp = REPLACE(REPLACE(father_whatsapp, '+KE', '+254'), 'KE+', '+254'),
            mother_whatsapp = REPLACE(REPLACE(mother_whatsapp, '+KE', '+254'), 'KE+', '+254')
            WHERE father_phone LIKE '%+KE%' 
               OR mother_phone LIKE '%+KE%' 
               OR guardian_phone LIKE '%+KE%'
               OR father_whatsapp LIKE '%+KE%'
               OR mother_whatsapp LIKE '%+KE%'
               OR father_phone LIKE '%KE+%'
               OR mother_phone LIKE '%KE+%'
               OR guardian_phone LIKE '%KE+%'
               OR father_whatsapp LIKE '%KE+%'
               OR mother_whatsapp LIKE '%KE+%'");
        
        // Fix country codes that are +KE or KE
        DB::statement("UPDATE parent_infos SET 
            father_phone_country_code = '+254'
            WHERE LOWER(father_phone_country_code) IN ('+ke', 'ke')");
        
        DB::statement("UPDATE parent_infos SET 
            mother_phone_country_code = '+254'
            WHERE LOWER(mother_phone_country_code) IN ('+ke', 'ke')");
        
        DB::statement("UPDATE parent_infos SET 
            guardian_phone_country_code = '+254'
            WHERE LOWER(guardian_phone_country_code) IN ('+ke', 'ke')");
        
        // Fix emergency contact phone in students table
        DB::statement("UPDATE students SET 
            emergency_contact_phone = REPLACE(REPLACE(emergency_contact_phone, '+KE', '+254'), 'KE+', '+254')
            WHERE emergency_contact_phone LIKE '%+KE%' OR emergency_contact_phone LIKE '%KE+%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback - this is a data fix
    }
};

