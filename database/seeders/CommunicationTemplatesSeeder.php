<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommunicationTemplate;

class CommunicationTemplatesSeeder extends Seeder
{
    public function run()
    {
        // Email Templates (stored in communication_templates with type 'email')
        CommunicationTemplate::updateOrInsert(
            ['code' => 'welcome_staff_email', 'type' => 'email'],
            [
                'title' => 'Welcome Staff',
                'subject' => 'Welcome to {school_name}',
                'content' => '<p>Dear {name},</p><p>Welcome to {school_name}. Your login credentials are:</p><ul><li><strong>Email:</strong> {login}</li><li><strong>Password:</strong> {password}</li></ul><p>Please change your password after your first login.</p><p>Regards,<br>{school_name} ICT Team</p>',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        CommunicationTemplate::updateOrInsert(
            ['code' => 'new_admission_email', 'type' => 'email'],
            [
                'title' => 'Student Admission',
                'subject' => 'Student Admission Confirmation',
                'content' => '<p>Dear Parent,</p><p>Your child <strong>{student_name}</strong> has been successfully admitted to {school_name}.</p><p>Class: <strong>{class}</strong></p><p>Welcome to our school community!</p>',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // SMS Templates
        CommunicationTemplate::updateOrInsert(
            ['code' => 'absent_notice_sms', 'type' => 'sms'],
            [
                'title' => 'Absent Notification',
                'subject' => null,
                'content' => 'Dear Parent, your child {student_name} from {class} is absent today. Reason: {reason}.',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        CommunicationTemplate::updateOrInsert(
            ['code' => 'welcome_staff_sms', 'type' => 'sms'],
            [
                'title' => 'Welcome Staff',
                'subject' => null,
                'content' => 'Welcome {name} to {school_name}. Login: {login} | Password: {password}',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        CommunicationTemplate::updateOrInsert(
            ['code' => 'new_admission_sms', 'type' => 'sms'],
            [
                'title' => 'Student Admission',
                'subject' => null,
                'content' => 'Dear Parent, your child {student_name} has been admitted to class {class}. Welcome to {school_name}.',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
