<?php

namespace App\Services;

use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;
use App\Models\Academics\Timetable;
use App\Models\Academics\ExtraCurricularActivity;
use App\Models\Academics\TimePeriod;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AI-Powered Timetable Optimization Service
 * 
 * This service uses constraint satisfaction and optimization algorithms
 * to generate optimal timetables without conflicts.
 * 
 * Can be extended to call Python scripts for more advanced AI algorithms.
 */
class TimetableOptimizationService
{
    /**
     * Generate optimized timetable for a classroom
     * 
     * @param int $classroomId
     * @param int $academicYearId
     * @param int $termId
     * @param array $options Optional configuration
     * @return array
     */
    public static function generateOptimized(
        int $classroomId,
        int $academicYearId,
        int $termId,
        array $options = []
    ): array {
        $classroom = Classroom::findOrFail($classroomId);
        
        // Get time periods for this level
        $timePeriods = self::getTimePeriodsForClassroom($classroom);
        $days = $options['days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        
        // Get subject assignments
        $assignments = self::getAssignments($classroomId, $academicYearId, $termId);
        
        // Get extra-curricular activities
        $activities = self::getExtraCurricularActivities($classroomId, $academicYearId, $termId);
        
        // Get teacher constraints (max lessons per week)
        $teacherLimits = self::getTeacherLimits($academicYearId, $termId);
        
        // Build constraint matrix
        $constraints = self::buildConstraints($assignments, $activities, $teacherLimits, $days, $timePeriods);
        
        // Generate timetable using constraint satisfaction
        $timetable = self::solveWithConstraints($constraints, $assignments, $activities, $days, $timePeriods);
        
        // Validate and check conflicts
        $conflicts = self::validateTimetable($timetable, $teacherLimits);
        
        // Calculate teacher lesson counts
        $teacherCounts = self::calculateTeacherCounts($timetable);
        
        return [
            'classroom' => $classroom,
            'academic_year_id' => $academicYearId,
            'term_id' => $termId,
            'time_periods' => $timePeriods,
            'days' => $days,
            'timetable' => $timetable,
            'conflicts' => $conflicts,
            'teacher_counts' => $teacherCounts,
            'assignments' => $assignments,
            'activities' => $activities,
        ];
    }

    /**
     * Get time periods for a classroom based on its level
     */
    protected static function getTimePeriodsForClassroom(Classroom $classroom): Collection
    {
        // Try to determine level from classroom name or metadata
        $levelName = self::determineLevelName($classroom);
        
        $periods = TimePeriod::getForLevel($levelName);
        
        // If no specific periods found, use default
        if ($periods->isEmpty()) {
            return self::getDefaultPeriods();
        }
        
        return $periods;
    }

    /**
     * Determine level name from classroom
     */
    protected static function determineLevelName(Classroom $classroom): string
    {
        $name = strtolower($classroom->name);
        
        // Simple heuristic - can be enhanced
        if (strpos($name, 'grade 1') !== false || strpos($name, 'grade 2') !== false || strpos($name, 'grade 3') !== false) {
            return 'Lower Primary';
        } elseif (strpos($name, 'grade 4') !== false || strpos($name, 'grade 5') !== false || strpos($name, 'grade 6') !== false) {
            return 'Upper Primary';
        } elseif (strpos($name, 'form 1') !== false || strpos($name, 'form 2') !== false) {
            return 'Lower Secondary';
        } elseif (strpos($name, 'form 3') !== false || strpos($name, 'form 4') !== false) {
            return 'Upper Secondary';
        }
        
        return 'Default';
    }

    /**
     * Get default time periods
     */
    protected static function getDefaultPeriods(): Collection
    {
        return collect([
            ['period_number' => 1, 'start_time' => '08:00', 'end_time' => '08:40', 'duration_minutes' => 40, 'is_break' => false],
            ['period_number' => 2, 'start_time' => '08:40', 'end_time' => '09:20', 'duration_minutes' => 40, 'is_break' => false],
            ['period_number' => 3, 'start_time' => '09:20', 'end_time' => '10:00', 'duration_minutes' => 40, 'is_break' => false],
            ['period_number' => 0, 'start_time' => '10:00', 'end_time' => '10:20', 'duration_minutes' => 20, 'is_break' => true, 'break_type' => 'morning_break'],
            ['period_number' => 4, 'start_time' => '10:20', 'end_time' => '11:00', 'duration_minutes' => 40, 'is_break' => false],
            ['period_number' => 5, 'start_time' => '11:00', 'end_time' => '11:40', 'duration_minutes' => 40, 'is_break' => false],
            ['period_number' => 6, 'start_time' => '11:40', 'end_time' => '12:20', 'duration_minutes' => 40, 'is_break' => false],
            ['period_number' => 0, 'start_time' => '12:20', 'end_time' => '13:00', 'duration_minutes' => 40, 'is_break' => true, 'break_type' => 'lunch'],
            ['period_number' => 7, 'start_time' => '13:00', 'end_time' => '13:40', 'duration_minutes' => 40, 'is_break' => false],
            ['period_number' => 8, 'start_time' => '13:40', 'end_time' => '14:20', 'duration_minutes' => 40, 'is_break' => false],
        ]);
    }

    /**
     * Get subject assignments for classroom
     */
    protected static function getAssignments(int $classroomId, int $academicYearId, int $termId): Collection
    {
        return ClassroomSubject::where('classroom_id', $classroomId)
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
    }

    /**
     * Get extra-curricular activities
     */
    protected static function getExtraCurricularActivities(int $classroomId, int $academicYearId, int $termId): Collection
    {
        return ExtraCurricularActivity::where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('is_active', true)
            ->where(function($q) use ($classroomId) {
                $q->whereJsonContains('classroom_ids', $classroomId)
                  ->orWhereNull('classroom_ids');
            })
            ->get();
    }

    /**
     * Get teacher lesson limits
     */
    protected static function getTeacherLimits(int $academicYearId, int $termId): array
    {
        $defaultMax = (int) setting('max_lessons_per_teacher_per_week', 40);
        
        $limits = [];
        $teachers = Staff::whereHas('user.roles', fn($q) => $q->whereIn('name', ['Teacher', 'teacher']))
            ->get();
        
        foreach ($teachers as $teacher) {
            // Check if teacher has custom limit (from staff meta or settings)
            $customLimit = $teacher->meta()->where('key', 'max_lessons_per_week')->value('value');
            $limits[$teacher->id] = $customLimit ? (int) $customLimit : $defaultMax;
        }
        
        return $limits;
    }

    /**
     * Build constraint matrix
     */
    protected static function buildConstraints(
        Collection $assignments,
        Collection $activities,
        array $teacherLimits,
        array $days,
        Collection $timePeriods
    ): array {
        $constraints = [
            'teachers' => [],
            'subjects' => [],
            'activities' => [],
            'periods' => [],
        ];
        
        // Teacher availability constraints
        foreach ($assignments as $assignment) {
            if ($assignment->teacher) {
                $teacherId = $assignment->teacher->id;
                if (!isset($constraints['teachers'][$teacherId])) {
                    $constraints['teachers'][$teacherId] = [
                        'max_lessons' => $teacherLimits[$teacherId] ?? 40,
                        'current_lessons' => 0,
                        'assignments' => [],
                    ];
                }
                $constraints['teachers'][$teacherId]['assignments'][] = $assignment;
            }
        }
        
        // Subject distribution constraints (avoid same subject twice in a day)
        foreach ($assignments as $assignment) {
            $subjectId = $assignment->subject_id;
            if (!isset($constraints['subjects'][$subjectId])) {
                $constraints['subjects'][$subjectId] = [
                    'lessons_per_week' => $assignment->lessons_per_week ?? 5,
                    'max_per_day' => 2, // Usually max 2 periods of same subject per day
                ];
            }
        }
        
        // Activity constraints
        foreach ($activities as $activity) {
            $constraints['activities'][] = [
                'day' => $activity->day,
                'start_time' => $activity->start_time,
                'end_time' => $activity->end_time,
                'period' => $activity->period,
            ];
        }
        
        return $constraints;
    }

    /**
     * Solve timetable using constraint satisfaction algorithm
     */
    protected static function solveWithConstraints(
        array $constraints,
        Collection $assignments,
        Collection $activities,
        array $days,
        Collection $timePeriods
    ): array {
        $timetable = [];
        $teacherSchedule = []; // Track teacher assignments
        $subjectSchedule = []; // Track subject assignments per day
        
        // Initialize timetable grid
        foreach ($days as $day) {
            $timetable[$day] = [];
            $subjectSchedule[$day] = [];
            
            foreach ($timePeriods as $period) {
                if ($period['is_break'] ?? false) {
                    $timetable[$day][$period['period_number']] = [
                        'type' => $period['break_type'] ?? 'break',
                        'start' => $period['start_time'],
                        'end' => $period['end_time'],
                        'is_break' => true,
                    ];
                    continue;
                }
                
                $timetable[$day][$period['period_number']] = [
                    'period' => $period['period_number'],
                    'start' => $period['start_time'],
                    'end' => $period['end_time'],
                    'subject' => null,
                    'teacher' => null,
                    'room' => null,
                ];
            }
        }
        
        // Add extra-curricular activities first
        foreach ($activities as $activity) {
            $day = $activity->day;
            $period = $activity->period ?? self::findPeriodForTime($timePeriods, $activity->start_time);
            
            if (isset($timetable[$day][$period])) {
                $timetable[$day][$period] = [
                    'type' => 'extra_curricular',
                    'name' => $activity->name,
                    'start' => $activity->start_time,
                    'end' => $activity->end_time,
                    'activity' => $activity,
                ];
            }
        }
        
        // Build assignment queue with priority
        $assignmentQueue = [];
        foreach ($assignments as $assignment) {
            $priority = $assignment->is_compulsory ? 10 : 5;
            $lessonsNeeded = $assignment->lessons_per_week ?? 5;
            
            for ($i = 0; $i < $lessonsNeeded; $i++) {
                $assignmentQueue[] = [
                    'assignment' => $assignment,
                    'priority' => $priority,
                ];
            }
        }
        
        // Sort by priority
        usort($assignmentQueue, fn($a, $b) => $b['priority'] <=> $a['priority']);
        
        // Assign subjects to periods
        foreach ($assignmentQueue as $item) {
            $assignment = $item['assignment'];
            $subject = $assignment->subject;
            $teacher = $assignment->teacher;
            
            if (!$teacher) {
                continue; // Skip if no teacher assigned
            }
            
            $teacherId = $teacher->id;
            $subjectId = $subject->id;
            
            // Find best slot
            $bestSlot = self::findBestSlot(
                $timetable,
                $teacherSchedule,
                $subjectSchedule,
                $teacherId,
                $subjectId,
                $days,
                $timePeriods,
                $constraints['teachers'][$teacherId]['max_lessons'] ?? 40
            );
            
            if ($bestSlot) {
                $timetable[$bestSlot['day']][$bestSlot['period']]['subject'] = $subject;
                $timetable[$bestSlot['day']][$bestSlot['period']]['teacher'] = $teacher;
                
                // Update tracking
                if (!isset($teacherSchedule[$teacherId])) {
                    $teacherSchedule[$teacherId] = [];
                }
                $teacherSchedule[$teacherId][] = [
                    'day' => $bestSlot['day'],
                    'period' => $bestSlot['period'],
                ];
                
                if (!isset($subjectSchedule[$bestSlot['day']][$subjectId])) {
                    $subjectSchedule[$bestSlot['day']][$subjectId] = 0;
                }
                $subjectSchedule[$bestSlot['day']][$subjectId]++;
            }
        }
        
        return $timetable;
    }

    /**
     * Find best slot for assignment
     */
    protected static function findBestSlot(
        array $timetable,
        array $teacherSchedule,
        array $subjectSchedule,
        int $teacherId,
        int $subjectId,
        array $days,
        Collection $timePeriods,
        int $maxLessons
    ): ?array {
        // Check teacher's current lesson count
        $currentLessons = isset($teacherSchedule[$teacherId]) ? count($teacherSchedule[$teacherId]) : 0;
        if ($currentLessons >= $maxLessons) {
            return null; // Teacher at limit
        }
        
        $bestSlot = null;
        $bestScore = -1;
        
        // Try each day and period
        foreach ($days as $day) {
            foreach ($timePeriods as $period) {
                if ($period['is_break'] ?? false) {
                    continue;
                }
                
                $periodNum = $period['period_number'];
                
                // Check if slot is available
                if (isset($timetable[$day][$periodNum]['subject']) && $timetable[$day][$periodNum]['subject'] !== null) {
                    continue; // Slot taken
                }
                
                // Check teacher conflict
                $hasConflict = false;
                if (isset($teacherSchedule[$teacherId])) {
                    foreach ($teacherSchedule[$teacherId] as $existing) {
                        if ($existing['day'] === $day && $existing['period'] === $periodNum) {
                            $hasConflict = true;
                            break;
                        }
                    }
                }
                
                if ($hasConflict) {
                    continue;
                }
                
                // Check subject distribution (avoid same subject twice in a day)
                $subjectCountToday = $subjectSchedule[$day][$subjectId] ?? 0;
                if ($subjectCountToday >= 2) {
                    continue; // Max 2 periods per day for same subject
                }
                
                // Calculate score (higher is better)
                $score = self::calculateSlotScore($day, $periodNum, $subjectSchedule, $days);
                
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestSlot = ['day' => $day, 'period' => $periodNum];
                }
            }
        }
        
        return $bestSlot;
    }

    /**
     * Calculate score for a slot (for optimization)
     */
    protected static function calculateSlotScore(
        string $day,
        int $period,
        array $subjectSchedule,
        array $days
    ): int {
        $score = 100;
        
        // Prefer morning periods (better learning)
        if ($period <= 3) {
            $score += 20;
        } elseif ($period <= 6) {
            $score += 10;
        }
        
        // Prefer spreading subjects across days
        $dayIndex = array_search($day, $days);
        $score += (5 - $dayIndex) * 2; // Earlier days slightly preferred
        
        return $score;
    }

    /**
     * Find period number for a given time
     */
    protected static function findPeriodForTime(Collection $timePeriods, string $time): int
    {
        foreach ($timePeriods as $period) {
            if ($period['start_time'] === $time) {
                return $period['period_number'];
            }
        }
        return 1; // Default
    }

    /**
     * Validate timetable and check for conflicts
     */
    protected static function validateTimetable(array $timetable, array $teacherLimits): array
    {
        $conflicts = [];
        $teacherSchedule = [];
        
        foreach ($timetable as $day => $periods) {
            foreach ($periods as $period => $data) {
                if (!isset($data['teacher']) || !$data['teacher']) {
                    continue;
                }
                
                $teacherId = $data['teacher']->id;
                
                if (!isset($teacherSchedule[$teacherId])) {
                    $teacherSchedule[$teacherId] = [];
                }
                
                $key = "{$day}_{$period}";
                if (isset($teacherSchedule[$teacherId][$key])) {
                    $conflicts[] = [
                        'type' => 'teacher_conflict',
                        'day' => $day,
                        'period' => $period,
                        'teacher_id' => $teacherId,
                        'teacher_name' => $data['teacher']->full_name ?? 'Unknown',
                    ];
                }
                
                $teacherSchedule[$teacherId][$key] = true;
            }
        }
        
        return $conflicts;
    }

    /**
     * Calculate teacher lesson counts
     */
    protected static function calculateTeacherCounts(array $timetable): array
    {
        $counts = [];
        
        foreach ($timetable as $day => $periods) {
            foreach ($periods as $period => $data) {
                if (isset($data['teacher']) && $data['teacher']) {
                    $teacherId = $data['teacher']->id;
                    if (!isset($counts[$teacherId])) {
                        $counts[$teacherId] = [
                            'teacher' => $data['teacher'],
                            'count' => 0,
                            'assignments' => [],
                        ];
                    }
                    $counts[$teacherId]['count']++;
                    $counts[$teacherId]['assignments'][] = [
                        'day' => $day,
                        'period' => $period,
                        'subject' => $data['subject'] ?? null,
                    ];
                }
            }
        }
        
        return $counts;
    }

    /**
     * Call Python script for advanced AI optimization (optional)
     * 
     * This method can be used to call a Python script that uses
     * more advanced algorithms like genetic algorithms, simulated annealing, etc.
     */
    public static function optimizeWithPython(array $timetableData): array
    {
        // This would call a Python script
        // Example: exec("python3 optimize_timetable.py " . escapeshellarg(json_encode($timetableData)));
        
        // For now, return the data as-is
        // In production, you would:
        // 1. Save timetable data to a JSON file
        // 2. Call Python script: python optimize_timetable.py input.json output.json
        // 3. Read optimized result from output.json
        // 4. Return optimized timetable
        
        return $timetableData;
    }
}

