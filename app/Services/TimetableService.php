<?php

namespace App\Services;

use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\Academics\ClassroomSubject;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TimetableService
{
    /**
     * Generate timetable for a classroom
     */
    public static function generateForClassroom(
        int $classroomId,
        int $academicYearId,
        int $termId,
        array $timeSlots = null,
        array $days = null
    ): array {
        $classroom = Classroom::findOrFail($classroomId);
        
        // Default time slots (8 periods per day)
        $timeSlots = $timeSlots ?? [
            ['start' => '08:00', 'end' => '08:40', 'period' => 1],
            ['start' => '08:40', 'end' => '09:20', 'period' => 2],
            ['start' => '09:20', 'end' => '10:00', 'period' => 3],
            ['start' => '10:00', 'end' => '10:20', 'period' => 'Break'],
            ['start' => '10:20', 'end' => '11:00', 'period' => 4],
            ['start' => '11:00', 'end' => '11:40', 'period' => 5],
            ['start' => '11:40', 'end' => '12:20', 'period' => 6],
            ['start' => '12:20', 'end' => '13:00', 'period' => 'Lunch'],
            ['start' => '13:00', 'end' => '13:40', 'period' => 7],
            ['start' => '13:40', 'end' => '14:20', 'period' => 8],
        ];

        // Default days
        $days = $days ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        // Get subject assignments for this classroom
        $assignments = ClassroomSubject::where('classroom_id', $classroomId)
            ->where(function($q) use ($academicYearId, $termId) {
                $q->where(function($q2) use ($academicYearId, $termId) {
                    $q2->where('academic_year_id', $academicYearId)
                       ->where('term_id', $termId);
                })
                ->orWhere(function($q2) {
                    $q2->whereNull('academic_year_id')
                       ->whereNull('term_id');
                });
            })
            ->with(['subject', 'teacher'])
            ->get();

        // Calculate total periods needed per subject
        $subjectPeriods = [];
        foreach ($assignments as $assignment) {
            $subjectId = $assignment->subject_id;
            if (!isset($subjectPeriods[$subjectId])) {
                $subjectPeriods[$subjectId] = [
                    'subject' => $assignment->subject,
                    'teacher' => $assignment->teacher,
                    'periods_per_week' => 5, // Default, can be configured
                    'is_compulsory' => $assignment->is_compulsory,
                ];
            }
        }

        // Generate timetable grid
        $timetable = [];
        $usedSlots = [];

        foreach ($days as $day) {
            $timetable[$day] = [];
            foreach ($timeSlots as $slot) {
                if (in_array($slot['period'], ['Break', 'Lunch'])) {
                    $timetable[$day][$slot['period']] = [
                        'type' => $slot['period'],
                        'start' => $slot['start'],
                        'end' => $slot['end'],
                    ];
                    continue;
                }

                $timetable[$day][$slot['period']] = [
                    'period' => $slot['period'],
                    'start' => $slot['start'],
                    'end' => $slot['end'],
                    'subject' => null,
                    'teacher' => null,
                ];
            }
        }

        // Assign subjects to periods (simple algorithm)
        $subjectQueue = [];
        foreach ($subjectPeriods as $subjectId => $data) {
            for ($i = 0; $i < $data['periods_per_week']; $i++) {
                $subjectQueue[] = $subjectId;
            }
        }

        shuffle($subjectQueue); // Randomize for better distribution

        $queueIndex = 0;
        foreach ($days as $day) {
            foreach ($timeSlots as $slot) {
                if (in_array($slot['period'], ['Break', 'Lunch'])) {
                    continue;
                }

                if ($queueIndex < count($subjectQueue)) {
                    $subjectId = $subjectQueue[$queueIndex];
                    $data = $subjectPeriods[$subjectId];
                    
                    $timetable[$day][$slot['period']]['subject'] = $data['subject'];
                    $timetable[$day][$slot['period']]['teacher'] = $data['teacher'];
                    
                    $queueIndex++;
                }
            }
        }

        return [
            'classroom' => $classroom,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'time_slots' => $timeSlots,
            'days' => $days,
            'timetable' => $timetable,
            'subjects' => $subjectPeriods,
        ];
    }

    /**
     * Generate timetable for a teacher
     */
    public static function generateForTeacher(
        int $staffId,
        int $academicYearId,
        int $termId
    ): array {
        $teacher = Staff::findOrFail($staffId);

        // Get all classroom assignments for this teacher
        $assignments = ClassroomSubject::where('staff_id', $staffId)
            ->where(function($q) use ($academicYearId, $termId) {
                $q->where(function($q2) use ($academicYearId, $termId) {
                    $q2->where('academic_year_id', $academicYearId)
                       ->where('term_id', $termId);
                })
                ->orWhere(function($q2) {
                    $q2->whereNull('academic_year_id')
                       ->whereNull('term_id');
                });
            })
            ->with(['classroom', 'subject'])
            ->get();

        $schedule = [];
        foreach ($assignments as $assignment) {
            $classroomId = $assignment->classroom_id;
            $timetable = self::generateForClassroom($classroomId, $academicYearId, $termId);
            
            // Find where this teacher appears in the timetable
            foreach ($timetable['timetable'] as $day => $periods) {
                foreach ($periods as $period => $data) {
                    if (isset($data['teacher']) && $data['teacher'] && $data['teacher']->id == $staffId) {
                        $schedule[] = [
                            'day' => $day,
                            'period' => $period,
                            'classroom' => $assignment->classroom,
                            'subject' => $assignment->subject,
                            'start' => $data['start'] ?? null,
                            'end' => $data['end'] ?? null,
                        ];
                    }
                }
            }
        }

        return [
            'teacher' => $teacher,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'schedule' => $schedule,
            'assignments' => $assignments,
        ];
    }

    /**
     * Check for timetable conflicts
     */
    public static function checkConflicts(array $timetable): array
    {
        $conflicts = [];

        foreach ($timetable['timetable'] as $day => $periods) {
            $teachers = [];
            $classrooms = [];

            foreach ($periods as $period => $data) {
                if (isset($data['teacher']) && $data['teacher']) {
                    $teacherId = $data['teacher']->id;
                    if (isset($teachers[$period][$teacherId])) {
                        $conflicts[] = [
                            'type' => 'teacher',
                            'day' => $day,
                            'period' => $period,
                            'teacher_id' => $teacherId,
                            'teacher_name' => $data['teacher']->full_name,
                        ];
                    }
                    $teachers[$period][$teacherId] = true;
                }
            }
        }

        return $conflicts;
    }
}

