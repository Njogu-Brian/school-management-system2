<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditAttendanceBeforeEnrolment extends Command
{
    protected $signature = 'attendance:audit-prior-to-enrolment
                            {--export= : Optional path to write CSV (e.g. storage/app/attendance_audit.csv)}';

    protected $description = 'List attendance rows where the date is before the student\'s enrolment date (admission_date)';

    public function handle(): int
    {
        $exportPath = $this->option('export');

        $rows = DB::table('attendance')
            ->join('students', 'students.id', '=', 'attendance.student_id')
            ->whereColumn('attendance.date', '<', 'students.admission_date')
            ->orderBy('attendance.date')
            ->orderBy('students.admission_number')
            ->select([
                'attendance.id as attendance_id',
                'attendance.student_id',
                'attendance.date as attendance_date',
                'attendance.status',
                'students.admission_number',
                'students.admission_date',
            ])
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No mismatched attendance records found (all attendance dates are on or after enrolment date).');

            return self::SUCCESS;
        }

        $this->warn('Found '.$rows->count().' attendance row(s) dated before the student\'s enrolment date:');
        $this->table(
            ['Attendance ID', 'Student ID', 'Adm #', 'Attendance date', 'Enrolment date', 'Status'],
            $rows->map(fn ($r) => [
                $r->attendance_id,
                $r->student_id,
                $r->admission_number,
                $r->attendance_date,
                $r->admission_date,
                $r->status,
            ])->toArray()
        );

        if ($exportPath) {
            $full = preg_match('#^([a-zA-Z]:)?[/\\\\]#', $exportPath) ? $exportPath : storage_path('app/'.ltrim($exportPath, '/'));
            $dir = dirname($full);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $fp = fopen($full, 'w');
            if ($fp === false) {
                $this->error('Could not write to: '.$full);

                return self::FAILURE;
            }
            fputcsv($fp, ['attendance_id', 'student_id', 'admission_number', 'attendance_date', 'admission_date', 'status']);
            foreach ($rows as $r) {
                fputcsv($fp, [
                    $r->attendance_id,
                    $r->student_id,
                    $r->admission_number,
                    $r->attendance_date,
                    $r->admission_date,
                    $r->status,
                ]);
            }
            fclose($fp);
            $this->info('Exported to: '.$full);
        }

        return self::SUCCESS;
    }
}
