<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Support\Collection;

class AttendanceReportService
{
    /**
     * All records grouped by date for class/stream in range.
     */
    public function recordsGroupedByDate($classId, $streamId, $startDate, $endDate): Collection
    {
        return Attendance::with(['student.classroom','student.stream'])
            ->whereBetween('date', [$startDate, $endDate])
            ->when($classId,  fn($q) => $q->whereHas('student', fn($sq) => $sq->where('classroom_id', $classId)))
            ->when($streamId, fn($q) => $q->whereHas('student', fn($sq) => $sq->where('stream_id', $streamId)))
            ->orderBy('date','desc')
            ->get()
            ->groupBy('date');
    }

    /**
     * Totals + gender breakdown for class/stream in range.
     */
    public function summary($classId, $streamId, $startDate, $endDate): array
    {
        $records = Attendance::with(['student:id,gender,classroom_id,stream_id'])
            ->whereBetween('date', [$startDate, $endDate])
            ->when($classId,  fn($q) => $q->whereHas('student', fn($sq) => $sq->where('classroom_id', $classId)))
            ->when($streamId, fn($q) => $q->whereHas('student', fn($sq) => $sq->where('stream_id', $streamId)))
            ->get();

        $totals = [
            'present' => $records->where('status','present')->count(),
            'absent'  => $records->where('status','absent')->count(),
            'late'    => $records->where('status','late')->count(),
        ];
        $totals['all'] = $totals['present'] + $totals['absent'] + $totals['late'];

        $byGender = [
            'male'   => ['present'=>0,'absent'=>0,'late'=>0],
            'female' => ['present'=>0,'absent'=>0,'late'=>0],
            'other'  => ['present'=>0,'absent'=>0,'late'=>0],
        ];

        foreach ($records as $r) {
            $g = strtolower($r->student->gender ?? 'other');
            if (!isset($byGender[$g])) $g = 'other';
            $byGender[$g][$r->status] = ($byGender[$g][$r->status] ?? 0) + 1;
        }

        return [
            'totals'  => $totals,
            'gender'  => $byGender,
        ];
    }

    /**
     * Stats for one student in a range (attendance %, counts).
     */
    public function studentStats(?Student $student, $startDate, $endDate): array
    {
        if (!$student) {
            return ['present'=>0,'absent'=>0,'late'=>0,'total'=>0,'percent'=>0];
        }
        $recs = Attendance::where('student_id',$student->id)
            ->whereBetween('date', [$startDate,$endDate])
            ->get();

        $present = $recs->where('status','present')->count();
        $absent  = $recs->where('status','absent')->count();
        $late    = $recs->where('status','late')->count();
        $total   = $recs->count();
        $percent = $total ? round(($present / $total) * 100, 1) : 0;

        return compact('present','absent','late','total','percent');
    }

    /**
     * For Kitchen: present count by class for a given date.
     */
    public function presentCountByClassForDate(string $date): array
    {
        $rows = Attendance::with('student.classroom')
            ->where('date', $date)
            ->where('status','present')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $className = optional($r->student->classroom)->name ?? 'Unknown';
            $out[$className] = ($out[$className] ?? 0) + 1;
        }
        ksort($out);
        return $out;
    }
}
