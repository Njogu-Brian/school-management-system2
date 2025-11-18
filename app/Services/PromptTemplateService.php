<?php

namespace App\Services;

use App\Models\CurriculumDesign;
use App\Models\Academics\CBCStrand;
use App\Models\Academics\CBCSubstrand;

class PromptTemplateService
{
    /**
     * Generate prompt for scheme of work
     */
    public function generateSchemeOfWorkPrompt(
        CurriculumDesign $curriculumDesign,
        array $context,
        array $retrievedChunks
    ): string {
        $strand = isset($context['strand_id']) ? CBCStrand::find($context['strand_id']) : null;
        $substrand = isset($context['substrand_id']) ? CBCSubstrand::find($context['substrand_id']) : null;
        $weeks = $context['weeks'] ?? 12;
        $classLevel = $context['class_level'] ?? $curriculumDesign->class_level;

        $chunksText = $this->formatChunks($retrievedChunks);
        
        $subjectName = $curriculumDesign->subject->name ?? 'N/A';
        $strandName = $strand->name ?? 'All strands';
        $substrandName = $substrand->name ?? 'All substrands';

        return <<<PROMPT
You are an expert curriculum designer creating a scheme of work for a Kenyan CBC/CBE curriculum.

Curriculum Context:
- Subject: {$subjectName}
- Class Level: {$classLevel}
- Duration: {$weeks} weeks
- Strand: {$strandName}
- Substrand: {$substrandName}

Relevant Curriculum Content:
{$chunksText}

Task: Generate a comprehensive {$weeks}-week scheme of work that includes:

1. Weekly Breakdown:
   - Week number
   - Learning objectives (specific, measurable)
   - Topics/Content to be covered
   - Suggested learning experiences/activities
   - Resources needed
   - Assessment methods (formative and summative)

2. Learning Progression:
   - Show how learning builds from week to week
   - Link to core competencies
   - Integration with other learning areas where relevant

3. Assessment Plan:
   - Weekly formative assessments
   - Mid-term assessment (if applicable)
   - End-of-term assessment

4. Differentiation:
   - Strategies for learners with different abilities
   - Extension activities for advanced learners
   - Support activities for struggling learners

Format the output as structured JSON with the following schema:
{
  "weeks": [
    {
      "week_number": 1,
      "learning_objectives": ["objective1", "objective2"],
      "topics": ["topic1", "topic2"],
      "learning_experiences": ["experience1", "experience2"],
      "resources": ["resource1", "resource2"],
      "formative_assessment": "description",
      "core_competencies": ["competency1", "competency2"]
    }
  ],
  "assessment_plan": {
    "formative": [...],
    "summative": {...}
  },
  "differentiation": {
    "advanced": [...],
    "support": [...]
  }
}

Ensure all content aligns with the Kenyan CBC curriculum framework and the provided curriculum design.
PROMPT;
    }

    /**
     * Generate prompt for lesson plan
     */
    public function generateLessonPlanPrompt(
        CurriculumDesign $curriculumDesign,
        array $context,
        array $retrievedChunks
    ): string {
        $substrand = isset($context['substrand_id']) ? CBCSubstrand::find($context['substrand_id']) : null;
        $week = $context['week'] ?? 1;
        $lessonNumber = $context['lesson_number'] ?? 1;

        $chunksText = $this->formatChunks($retrievedChunks);
        
        $subjectName = $curriculumDesign->subject->name ?? 'N/A';
        $classLevel = $context['class_level'] ?? $curriculumDesign->class_level;
        $substrandName = $substrand->name ?? 'N/A';
        $duration = $context['duration'] ?? 40;

        return <<<PROMPT
You are an expert teacher creating a detailed lesson plan for a Kenyan CBC/CBE curriculum.

Lesson Context:
- Subject: {$subjectName}
- Class Level: {$classLevel}
- Week: {$week}
- Lesson Number: {$lessonNumber}
- Substrand: {$substrandName}
- Duration: {$duration} minutes

Relevant Curriculum Content:
{$chunksText}

Task: Generate a comprehensive lesson plan that includes:

1. Lesson Information:
   - Lesson title
   - Date
   - Time allocation
   - Class/Stream

2. Learning Objectives:
   - Specific learning outcomes
   - Success criteria

3. Learning Resources:
   - Teaching materials
   - Learning materials
   - Technology (if applicable)

4. Lesson Development:
   - Introduction (5-10 minutes)
   - Main activities (25-30 minutes)
   - Conclusion (5-10 minutes)
   - Step-by-step teaching procedures

5. Assessment:
   - Formative assessment strategies
   - Questions to check understanding
   - Observation checklist

6. Differentiation:
   - Activities for different ability levels
   - Extension tasks
   - Support strategies

7. Homework/Follow-up:
   - Assignment or follow-up activities

Format the output as structured JSON:
{
  "lesson_title": "...",
  "learning_objectives": [...],
  "success_criteria": [...],
  "resources": [...],
  "lesson_development": {
    "introduction": {...},
    "main_activities": [...],
    "conclusion": {...}
  },
  "assessment": {...},
  "differentiation": {...},
  "homework": "..."
}

Ensure the lesson plan is practical, engaging, and aligned with CBC pedagogy.
PROMPT;
    }

    /**
     * Generate prompt for assessment items
     */
    public function generateAssessmentPrompt(
        CurriculumDesign $curriculumDesign,
        array $context,
        array $retrievedChunks
    ): string {
        $substrand = isset($context['substrand_id']) ? CBCSubstrand::find($context['substrand_id']) : null;
        $count = $context['count'] ?? 10;
        $difficulty = $context['difficulty'] ?? 'mixed';

        $chunksText = $this->formatChunks($retrievedChunks);
        
        $subjectName = $curriculumDesign->subject->name ?? 'N/A';
        $classLevel = $context['class_level'] ?? $curriculumDesign->class_level;
        $substrandName = $substrand->name ?? 'N/A';
        $assessmentType = $context['assessment_type'] ?? 'formative';

        return <<<PROMPT
You are an expert assessment designer creating assessment items for a Kenyan CBC/CBE curriculum.

Assessment Context:
- Subject: {$subjectName}
- Class Level: {$classLevel}
- Substrand: {$substrandName}
- Number of items: {$count}
- Difficulty: {$difficulty}
- Assessment type: {$assessmentType}

Relevant Curriculum Content:
{$chunksText}

Task: Generate {$count} assessment items that:

1. Align with the curriculum competencies
2. Cover different cognitive levels (remembering, understanding, applying, analyzing, evaluating, creating)
3. Include various question types:
   - Multiple choice questions
   - Short answer questions
   - Structured questions
   - Performance tasks (where applicable)

4. Each item should include:
   - Question text
   - Answer/marking scheme
   - Marks allocation
   - Competency being assessed
   - Difficulty level
   - Cognitive level

Format the output as structured JSON:
{
  "items": [
    {
      "question_number": 1,
      "question_type": "multiple_choice|short_answer|structured|performance",
      "question_text": "...",
      "options": [...], // for MCQ
      "correct_answer": "...",
      "marking_scheme": "...",
      "marks": 2,
      "competency": "...",
      "difficulty": "easy|medium|hard",
      "cognitive_level": "remembering|understanding|applying|analyzing|evaluating|creating"
    }
  ],
  "total_marks": 20,
  "assessment_summary": {
    "competencies_covered": [...],
    "difficulty_distribution": {...}
  }
}

Ensure questions are age-appropriate, clear, and aligned with CBC assessment principles.
PROMPT;
    }

    /**
     * Generate prompt for report card content
     */
    public function generateReportCardPrompt(
        CurriculumDesign $curriculumDesign,
        array $context,
        array $retrievedChunks
    ): string {
        $chunksText = $this->formatChunks($retrievedChunks);
        $classLevel = $context['class_level'] ?? $curriculumDesign->class_level;
        $subjectName = $curriculumDesign->subject->name ?? 'N/A';
        $performanceData = $context['performance_data'] ?? 'N/A';

        return <<<PROMPT
You are an expert teacher creating report card comments for a Kenyan CBC/CBE curriculum.

Context:
- Subject: {$subjectName}
- Class Level: {$classLevel}
- Student Performance Data: {$performanceData}

Relevant Curriculum Content:
{$chunksText}

Task: Generate comprehensive report card content including:

1. Academic Performance Summary
2. Competency-based Assessment Comments
3. Strengths and Areas for Improvement
4. Recommendations for Next Term

Format as structured JSON with appropriate sections aligned to CBC reporting requirements.
PROMPT;
    }

    /**
     * Format retrieved chunks for prompt
     */
    protected function formatChunks(array $chunks): string
    {
        $formatted = [];
        foreach ($chunks as $index => $chunk) {
            $formatted[] = "Chunk " . ($index + 1) . ":\n" . ($chunk['text_snippet'] ?? $chunk['text'] ?? '');
        }
        return implode("\n\n---\n\n", $formatted);
    }
}

