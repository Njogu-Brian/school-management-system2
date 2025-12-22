<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommunicationTemplate;

class CommunicationTemplatesSeeder extends Seeder
{
    public function run()
    {
        // ✅ Email Templates (now in communication_templates table with type='email')
        CommunicationTemplate::updateOrInsert(
            ['code' => 'welcome_staff'],
            [
                'title' => 'Welcome Staff',
                'type' => 'email',
                'subject' => 'Welcome Staff',
                'content' => '<p>Dear {name},</p><p>Welcome to Royal Kings School. Your login credentials are:</p><ul><li><strong>Email:</strong> {login}</li><li><strong>Password:</strong> {password}</li></ul><p>Please change your password after your first login.</p><p>Regards,<br>Royal Kings ICT Team</p>',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        CommunicationTemplate::updateOrInsert(
            ['code' => 'new_admission'],
            [
                'title' => 'Student Admission',
                'type' => 'email',
                'subject' => 'Student Admission',
                'content' => '<p>Dear Parent,</p><p>Your child <strong>{student_name}</strong> has been successfully admitted to Royal Kings School.</p><p>Class: <strong>{class}</strong></p><p>Welcome to our school community!</p>',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // ✅ SMS Templates
        CommunicationTemplate::updateOrInsert(
            ['code' => 'absent_notice'],
            [
                'title' => 'Absent Notification',
                'type' => 'sms',
                'subject' => null,
                'content' => 'Dear Parent, your child {student_name} from {class} is absent today. Reason: {reason}.',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        CommunicationTemplate::updateOrInsert(
            ['code' => 'welcome_staff_sms'],
            [
                'title' => 'Welcome Staff (SMS)',
                'type' => 'sms',
                'subject' => null,
                'content' => 'Welcome {name} to Royal Kings School. Login: {login} | Password: {password}',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        CommunicationTemplate::updateOrInsert(
            ['code' => 'new_admission_sms'],
            [
                'title' => 'Student Admission (SMS)',
                'type' => 'sms',
                'subject' => null,
                'content' => 'Dear Parent, your child {student_name} has been admitted to class {class}. Welcome to Royal Kings.',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
