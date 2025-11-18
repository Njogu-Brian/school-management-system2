<?php

namespace App\Services;

use App\Models\Academics\SchemeOfWork;
use App\Models\Academics\Subject;
use App\Models\Academics\Classroom;
use App\Models\Academics\CBCStrand;
use App\Models\Academics\CBCSubstrand;
use App\Models\Academics\LearningArea;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchemeOfWorkAutoGenerationService
{
    /**
     * Auto-generate scheme of work based on CBC curriculum
     */
    public function generate(array $data): SchemeOfWork
    {
        DB::beginTransaction();
        try {
            $subject = Subject::findOrFail($data['subject_id']);
            $classroom = Classroom::findOrFail($data['classroom_id']);
            $academicYear = AcademicYear::findOrFail($data['academic_year_id']);
            $term = Term::findOrFail($data['term_id']);

            // Get learning area from subject or classroom
            $learningArea = $this->getLearningArea($subject, $classroom);
            
            // Get strands for this learning area and level
            $strands = $this->getStrands($learningArea, $subject, $classroom);
            
            // Get substrands for selected strands
            $substrands = $this->getSubstrands($strands, $data['strand_ids'] ?? []);
            
            // Calculate total lessons based on substrands
            $totalLessons = $this->calculateTotalLessons($substrands, $data);
            
            // Generate title
            $title = $this->generateTitle($subject, $classroom, $academicYear, $term);
            
            // Create scheme of work
            $schemeOfWork = SchemeOfWork::create([
                'subject_id' => $subject->id,
                'classroom_id' => $classroom->id,
                'academic_year_id' => $academicYear->id,
                'term_id' => $term->id,
                'created_by' => Auth::user()->staff?->id,
                'title' => $title,
                'description' => $data['description'] ?? $this->generateDescription($subject, $classroom, $term),
                'total_lessons' => $totalLessons,
                'lessons_completed' => 0,
                'status' => $data['status'] ?? 'draft',
                'strands_coverage' => $strands->pluck('id')->toArray(),
                'substrands_coverage' => $substrands->pluck('id')->toArray(),
                'general_remarks' => $data['general_remarks'] ?? null,
            ]);

            DB::commit();
            
            Log::info('Scheme of work auto-generated', [
                'scheme_id' => $schemeOfWork->id,
                'subject' => $subject->name,
                'classroom' => $classroom->name,
            ]);

            return $schemeOfWork;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to auto-generate scheme of work', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Get learning area from subject or classroom
     */
    protected function getLearningArea(Subject $subject, Classroom $classroom): ?LearningArea
    {
        // Try to get learning area from subject's learning_area field
        if ($subject->learning_area) {
            $learningArea = LearningArea::where('code', $subject->learning_area)
                ->orWhere('name', $subject->learning_area)
                ->first();
            
            if ($learningArea) {
                return $learningArea;
            }
        }

        // Try to get learning area from subject name
        $learningArea = LearningArea::where('name', 'like', '%' . $subject->name . '%')
            ->orWhere('code', 'like', '%' . strtoupper(substr($subject->name, 0, 3)) . '%')
            ->first();

        return $learningArea;
    }

    /**
     * Get strands for learning area and level
     */
    protected function getStrands(?LearningArea $learningArea, Subject $subject, Classroom $classroom)
    {
        if (!$learningArea) {
            // Fallback to old method using learning_area string
            return CBCStrand::where(function($q) use ($subject) {
                    $q->where('learning_area', $subject->learning_area ?? $subject->name)
                      ->orWhereHas('learningArea', function($subQ) use ($subject) {
                          $subQ->where('code', $subject->learning_area ?? $subject->name)
                               ->orWhere('name', $subject->learning_area ?? $subject->name);
                      });
                })
                ->where('level', $subject->level ?? $classroom->level ?? '')
                ->where('is_active', true)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get();
        }

        // Use learning_area_id if available
        $query = CBCStrand::where('learning_area_id', $learningArea->id);
        
        // Filter by level if available
        if ($subject->level || $classroom->level) {
            $level = $subject->level ?? $classroom->level;
            $query->where('level', $level);
        }

        return $query->where('is_active', true)->orderBy('display_order')->orderBy('name')->get();
    }

    /**
     * Get substrands for selected strands
     */
    protected function getSubstrands($strands, array $strandIds = [])
    {
        if (empty($strandIds)) {
            // Use all strands if none selected
            $strandIds = $strands->pluck('id')->toArray();
        }

        return CBCSubstrand::whereIn('strand_id', $strandIds)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Calculate total lessons based on substrands
     */
    protected function calculateTotalLessons($substrands, array $data): int
    {
        // If total_lessons is provided, use it
        if (isset($data['total_lessons']) && $data['total_lessons'] > 0) {
            return (int) $data['total_lessons'];
        }

        // Calculate from substrands (default 5 lessons per substrand if not specified)
        $totalLessons = $substrands->count() * 5; // Default 5 lessons per substrand
        
        // If substrands have a suggested_lessons field, use it
        if ($substrands->first() && isset($substrands->first()->suggested_lessons)) {
            $totalLessons = $substrands->sum(function($substrand) {
                return $substrand->suggested_lessons ?? 5;
            });
        }

        // Apply multiplier if provided
        if (isset($data['lessons_multiplier'])) {
            $totalLessons = (int) ($totalLessons * $data['lessons_multiplier']);
        }

        return max($totalLessons, 1); // Minimum 1 lesson
    }

    /**
     * Generate title for scheme of work
     */
    protected function generateTitle(Subject $subject, Classroom $classroom, AcademicYear $academicYear, Term $term): string
    {
        return sprintf(
            '%s - %s - %s - %s',
            $subject->name,
            $classroom->name,
            $academicYear->year,
            $term->name
        );
    }

    /**
     * Generate description for scheme of work
     */
    protected function generateDescription(Subject $subject, Classroom $classroom, Term $term): string
    {
        return sprintf(
            'Scheme of work for %s in %s for %s term. Auto-generated from CBC curriculum.',
            $subject->name,
            $classroom->name,
            $term->name
        );
    }

    /**
     * Generate lesson plans from scheme of work
     */
    public function generateLessonPlans(SchemeOfWork $schemeOfWork, array $options = []): array
    {
        $lessonPlans = [];
        $substrands = CBCSubstrand::whereIn('id', $schemeOfWork->substrands_coverage ?? [])
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $lessonNumber = 1;
        $currentDate = $options['start_date'] ?? now()->toDateString();
        $lessonsPerWeek = $options['lessons_per_week'] ?? 5;

        foreach ($substrands as $substrand) {
            // Default to 5 lessons per substrand if not specified
            $lessonsForSubstrand = property_exists($substrand, 'suggested_lessons') 
                ? ($substrand->suggested_lessons ?? 5) 
                : 5;
            
            for ($i = 0; $i < $lessonsForSubstrand; $i++) {
                // Calculate date (skip weekends if needed)
                $plannedDate = $this->calculateLessonDate($currentDate, $lessonNumber, $lessonsPerWeek);
                
                $lessonPlan = $schemeOfWork->lessonPlans()->create([
                    'subject_id' => $schemeOfWork->subject_id,
                    'classroom_id' => $schemeOfWork->classroom_id,
                    'substrand_id' => $substrand->id,
                    'academic_year_id' => $schemeOfWork->academic_year_id,
                    'term_id' => $schemeOfWork->term_id,
                    'created_by' => Auth::user()->staff?->id,
                    'title' => sprintf('%s - Lesson %d', $substrand->name, $lessonNumber),
                    'lesson_number' => 'Lesson ' . $lessonNumber,
                    'planned_date' => $plannedDate,
                    'duration_minutes' => $options['duration_minutes'] ?? 40,
                    'learning_objectives' => $this->extractLearningObjectives($substrand),
                    'learning_outcomes' => $substrand->learning_outcomes,
                    'core_competencies' => $this->parseJsonField($substrand->core_competencies),
                    'values' => $this->parseJsonField($substrand->values),
                    'pclc' => $this->parseJsonField($substrand->pclc),
                    'learning_resources' => $this->parseJsonField($substrand->learning_resources ?? []),
                    'status' => 'planned',
                ]);

                $lessonPlans[] = $lessonPlan;
                $lessonNumber++;
            }
        }

        // Update scheme of work total lessons
        $schemeOfWork->update([
            'total_lessons' => count($lessonPlans),
        ]);

        return $lessonPlans;
    }

    /**
     * Calculate lesson date based on week schedule
     */
    protected function calculateLessonDate(string $startDate, int $lessonNumber, int $lessonsPerWeek): string
    {
        $start = new \DateTime($startDate);
        $weekNumber = floor(($lessonNumber - 1) / $lessonsPerWeek);
        $dayInWeek = ($lessonNumber - 1) % $lessonsPerWeek;
        
        // Add weeks
        $start->modify("+{$weekNumber} weeks");
        
        // Add days (skip weekends - Saturday=6, Sunday=0)
        $daysAdded = 0;
        while ($daysAdded < $dayInWeek) {
            $start->modify('+1 day');
            $dayOfWeek = (int) $start->format('w');
            if ($dayOfWeek != 0 && $dayOfWeek != 6) {
                $daysAdded++;
            }
        }
        
        return $start->format('Y-m-d');
    }

    /**
     * Extract learning objectives from substrand
     */
    protected function extractLearningObjectives(CBCSubstrand $substrand): array
    {
        $objectives = [];
        
        if ($substrand->learning_outcomes) {
            $outcomes = is_array($substrand->learning_outcomes) 
                ? $substrand->learning_outcomes 
                : json_decode($substrand->learning_outcomes, true);
            
            if (is_array($outcomes)) {
                $objectives = $outcomes;
            } else {
                // Split by newlines or bullets
                $objectives = preg_split('/\n|•|-\s*/', $substrand->learning_outcomes, -1, PREG_SPLIT_NO_EMPTY);
                $objectives = array_map('trim', $objectives);
            }
        }
        
        return array_filter($objectives);
    }

    /**
     * Parse JSON field (handle both JSON strings and arrays)
     */
    protected function parseJsonField($field): array
    {
        if (is_array($field)) {
            return $field;
        }
        
        if (is_string($field)) {
            $decoded = json_decode($field, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded ?? [];
            }
            
            // Try splitting by comma or newline
            return array_filter(array_map('trim', preg_split('/,|\n/', $field)));
        }
        
        return [];
    }
}

