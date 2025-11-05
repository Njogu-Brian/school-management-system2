<?php

namespace App\Services;

use App\Models\Attendance;

class AttendanceSummary
{
    public static function forStudentTerm(int $studentId, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $atts = Attendance::where('student_id', $studentId)
            ->whereBetween('date', [$start, $end])
            ->get();

        $present = $atts->where('status','present')->count();
        $absent  = $atts->where('status','absent')->count();
        $late    = $atts->where('status','late')->count();
        $total   = max(1, $present + $absent + $late);

        return [
            'present' => $present,
            'absent'  => $absent,
            'late'    => $late,
            'percent' => round(($present / $total) * 100, 1),
        ];
    }
}
