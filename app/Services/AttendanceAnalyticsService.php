<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AttendanceAnalyticsService
{
    public function __construct(
        protected StudentAttendanceCalendarService $attendanceCalendar
    ) {
    }

    /**
     * Calculate attendance percentage for a student
     * Denominator: expected school days in range after enrolment; numerator: present + late (attending days).
     */
    public function calculateAttendancePercentage(Student $student, $startDate = null, $endDate = null): float
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();

        $totalSchoolDays = $this->attendanceCalendar->expectedSchoolDaysBetween($student, $startDate, $endDate, null);

        $presentDays = Attendance::where('student_id', $student->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'present')
            ->count();

        $lateDays = Attendance::where('student_id', $student->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'late')
            ->count();

        $attendingDays = $presentDays + $lateDays;

        if ($totalSchoolDays === 0) {
            return 0;
        }

        return round(($attendingDays / $totalSchoolDays) * 100, 2);
    }

    /**
     * Get at-risk students (low attendance)
     */
    public function getAtRiskStudents($classroomId = null, $streamId = null, $threshold = 75.0, $startDate = null, $endDate = null): Collection
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();

        $students = Student::query()
            ->when($classroomId, fn($q) => $q->where('classroom_id', $classroomId))
            ->when($streamId, fn($q) => $q->where('stream_id', $streamId))
            ->where('status', 'active')
            ->get();

        $atRisk = collect();

        foreach ($students as $student) {
            $percentage = $this->calculateAttendancePercentage($student, $startDate, $endDate);
            
            if ($percentage < $threshold) {
                $atRisk->push([
                    'student' => $student,
                    'percentage' => $percentage,
                    'present_days' => Attendance::where('student_id', $student->id)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->where('status', 'present')
                        ->count(),
                    'total_days' => $this->attendanceCalendar->expectedSchoolDaysBetween($student, $startDate, $endDate, null),
                ]);
            }
        }

        return $atRisk->sortBy('percentage');
    }

    /**
     * Get consecutive absence count for a student
     */
    public function getConsecutiveAbsences(Student $student, $asOfDate = null): int
    {
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate)->startOfDay() : Carbon::today();
        $floor = $this->attendanceCalendar->effectiveEnrolmentDate($student);

        $consecutive = 0;
        $currentDate = $asOfDate->copy();

        while ($currentDate->gte($floor) && $consecutive < 365) {
            if (! $this->attendanceCalendar->isAttendanceSessionDayForStudent($student, $currentDate)) {
                $currentDate->subDay();

                continue;
            }

            $attendance = Attendance::where('student_id', $student->id)
                ->whereDate('date', $currentDate)
                ->first();

            if (! $attendance) {
                $consecutive++;
            } elseif ($attendance->status === 'present') {
                break;
            } elseif (in_array($attendance->status, ['absent', 'late'], true)) {
                $consecutive++;
            }

            $currentDate->subDay();
        }

        return $consecutive;
    }

    /**
     * Get attendance trends for a student
     */
    public function getAttendanceTrends(Student $student, $months = 6): array
    {
        $trends = [];
        $endDate = Carbon::now()->endOfMonth();
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $startDate = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            
            $percentage = $this->calculateAttendancePercentage($student, $startDate, $monthEnd);
            
            $trends[] = [
                'month' => $startDate->format('M Y'),
                'start_date' => $startDate->toDateString(),
                'end_date' => $monthEnd->toDateString(),
                'percentage' => $percentage,
                'present' => Attendance::where('student_id', $student->id)
                    ->whereBetween('date', [$startDate, $monthEnd])
                    ->where('status', 'present')
                    ->count(),
                'absent' => Attendance::where('student_id', $student->id)
                    ->whereBetween('date', [$startDate, $monthEnd])
                    ->where('status', 'absent')
                    ->count(),
                'late' => Attendance::where('student_id', $student->id)
                    ->whereBetween('date', [$startDate, $monthEnd])
                    ->where('status', 'late')
                    ->count(),
            ];
        }

        return $trends;
    }

    /**
     * Get attendance statistics by subject
     */
    public function getSubjectAttendanceStats($studentId, $subjectId, $startDate = null, $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();

        $records = Attendance::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $total = $records->count();
        $present = $records->where('status', 'present')->count();
        $absent = $records->where('status', 'absent')->count();
        $late = $records->where('status', 'late')->count();

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get students with consecutive absences above threshold
     */
    public function getStudentsWithConsecutiveAbsences($threshold = 3, $classroomId = null, $streamId = null): Collection
    {
        $students = Student::query()
            ->when($classroomId, fn($q) => $q->where('classroom_id', $classroomId))
            ->when($streamId, fn($q) => $q->where('stream_id', $streamId))
            ->where('status', 'active')
            ->get();

        $result = collect();

        foreach ($students as $student) {
            $consecutive = $this->getConsecutiveAbsences($student);
            
            if ($consecutive >= $threshold) {
                $result->push([
                    'student' => $student,
                    'consecutive_absences' => $consecutive,
                ]);
            }
        }

        return $result->sortByDesc('consecutive_absences');
    }

    /**
     * Update consecutive absence count for all students
     */
    public function updateConsecutiveAbsenceCounts(): void
    {
        $students = Student::where('status', 'active')->get();

        foreach ($students as $student) {
            $consecutive = $this->getConsecutiveAbsences($student);
            
            // Update the most recent attendance record
            $latestAttendance = Attendance::where('student_id', $student->id)
                ->latest('date')
                ->first();

            if ($latestAttendance) {
                $latestAttendance->update(['consecutive_absence_count' => $consecutive]);
            }
        }
    }
}

