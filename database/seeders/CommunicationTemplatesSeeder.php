<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\EmailTemplate;
use App\Models\CommunicationTemplate;

class CommunicationTemplatesSeeder extends Seeder
{
    public function run()
    {
        // ✅ Email Templates
        EmailTemplate::updateOrInsert(
            ['code' => 'welcome_staff'],
            [
                'title' => 'Welcome Staff',
                'message' => '<p>Dear {name},</p><p>Welcome to Royal Kings School. Your login credentials are:</p><ul><li><strong>Email:</strong> {login}</li><li><strong>Password:</strong> {password}</li></ul><p>Please change your password after your first login.</p><p>Regards,<br>Royal Kings ICT Team</p>',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        EmailTemplate::updateOrInsert(
            ['code' => 'new_admission'],
            [
                'title' => 'Student Admission',
                'message' => '<p>Dear Parent,</p><p>Your child <strong>{student_name}</strong> has been successfully admitted to Royal Kings School.</p><p>Class: <strong>{class}</strong></p><p>Welcome to our school community!</p>',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // ✅ SMS Templates (in communication_templates table)
        CommunicationTemplate::updateOrInsert(
            ['code' => 'absent_notice'],
            [
                'title' => 'Absent Notification',
                'type' => 'sms',
                'content' => 'Dear Parent, your child {student_name} from {class} is absent today. Reason: {reason}.',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        CommunicationTemplate::updateOrInsert(
            ['code' => 'welcome_staff'],
            [
                'title' => 'Welcome Staff',
                'type' => 'sms',
                'content' => 'Welcome {name} to Royal Kings School. Login: {login} | Password: {password}',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        CommunicationTemplate::updateOrInsert(
            ['code' => 'new_admission'],
            [
                'title' => 'Student Admission',
                'type' => 'sms',
                'content' => 'Dear Parent, your child {student_name} has been admitted to class {class}. Welcome to Royal Kings.',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
