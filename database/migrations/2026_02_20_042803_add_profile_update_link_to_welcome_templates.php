<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add {{profile_update_link}} to admissions welcome SMS/WhatsApp templates
     * so the profile update link is included when sending welcome messages.
     */
    public function up(): void
    {
        $newContent = "Dear {{parent_name}},\n\nWelcome to {{school_name}}! 🎉\nWe are delighted to inform you that {{student_name}} has been successfully admitted.\n\nAdmission Number: {{admission_number}}\nClass: {{class_name}} {{stream_name}}\n\nUpdate your profile: {{profile_update_link}}\n\nWarm regards,\n{{school_name}}";

        DB::table('communication_templates')
            ->whereIn('code', ['admissions_welcome_sms', 'admissions_welcome_whatsapp'])
            ->update(['content' => $newContent, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Cannot reliably restore previous content
    }
};
