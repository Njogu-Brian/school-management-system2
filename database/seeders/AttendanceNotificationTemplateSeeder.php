<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommunicationTemplate;
use Carbon\Carbon;

class AttendanceNotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $tpl = [
            'code'    => 'attendance_daily_summary',
            'title'   => 'Daily Attendance Summary',
            'type'    => 'sms',
            'subject' => null,
            'content' => 'Hello {label}, here is the attendance summary for {date}. Please check the system for full details.',
        ];

        CommunicationTemplate::updateOrCreate(
            ['code' => $tpl['code']],
            array_merge($tpl, ['updated_at' => $now, 'created_at' => $now])
        );
    }
}
