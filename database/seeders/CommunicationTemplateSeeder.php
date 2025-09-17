<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommunicationTemplate;
use Carbon\Carbon;

class CommunicationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $templates = [
           [
    'code'    => 'attendance_absent',
    'title'   => 'Absent Notification',
    'type'    => 'sms',
    'subject' => null,
    'content' => 'Dear {parent_name}, your child {student_name} from {class} was marked ABSENT on {date}. Reason: {reason}',
],
[
    'code'    => 'attendance_late',
    'title'   => 'Late Notification',
    'type'    => 'sms',
    'subject' => null,
    'content' => 'Dear {parent_name}, your child {student_name} from {class} was marked LATE on {date}. Reason: {reason}',
],
[
    'code'    => 'attendance_corrected',
    'title'   => 'Correction Notification',
    'type'    => 'sms',
    'subject' => null,
    'content' => 'Update: {student_name}, previously marked ABSENT, has been corrected to PRESENT on {date}.',
],
              // Add more templates as needed
        ];

        foreach ($templates as $tpl) {
            CommunicationTemplate::updateOrCreate(
                ['code' => $tpl['code']],
                array_merge($tpl, ['updated_at' => $now, 'created_at' => $now])
            );
        }
    }
}
