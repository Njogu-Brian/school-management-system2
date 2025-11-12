# CBC Compliance Implementation Guide

## Executive Summary

This document outlines the implementation of Competency-Based Curriculum (CBC) compliance features for the school management system's academics and exams modules.

## Current State Analysis

### ✅ Already Implemented
- Exam types with 'cbc' calculation method
- Basic rubrics support (JSON field)
- Performance levels field (`pl_level`)
- Grade labels (`grade_label`)
- CAT/RAT exam types
- Subject learning areas

### ❌ Missing Critical Features
1. **Performance Level Descriptors** - Standardized descriptors for Exceeding, Meeting, Approaching, Below
2. **Continuous Assessment Tracking** - Aggregation of CATs and continuous assessments
3. **Portfolio Assessments** - Project-based and portfolio evidence tracking
4. **Core Competencies Assessment** - 7 core competencies evaluation
5. **Multiple Assessment Methods** - Oral, practical, written tracking
6. **Competency-Based Rubrics** - Detailed competency rubrics with descriptors
7. **Learning Area Assessment** - Assessment at learning area level vs subject level

## Implementation Plan

### Phase 1: Performance Levels & Descriptors

**Purpose**: Standardize performance level assessment according to CBC guidelines.

**Components**:
- Performance Level model with descriptors
- Integration with exam marks
- Display in reports and transcripts

**Performance Levels**:
1. **Exceeding** (E) - 80-100% - Learner demonstrates competencies beyond expected level
2. **Meeting** (M) - 60-79% - Learner demonstrates competencies at expected level
3. **Approaching** (A) - 40-59% - Learner demonstrates competencies approaching expected level
4. **Below** (B) - 0-39% - Learner demonstrates competencies below expected level

### Phase 2: Continuous Assessment Tests (CATs)

**Purpose**: Track and aggregate continuous assessments throughout the term.

**Components**:
- CAT tracking per subject
- Automatic aggregation
- Weighted contribution to term assessment
- Multiple CATs per term support

**CAT Structure**:
- CAT 1 (Week 1-4)
- CAT 2 (Week 5-8)
- CAT 3 (Week 9-12)
- Each contributes to term assessment

### Phase 3: Portfolio & Project Assessments

**Purpose**: Track project-based and portfolio evidence.

**Components**:
- Portfolio assessment model
- Project tracking
- Evidence uploads
- Rubric-based evaluation
- Integration with term assessment

**Portfolio Types**:
- Individual Projects
- Group Projects
- Practical Work
- Creative Works
- Research Projects

### Phase 4: Core Competencies Assessment

**Purpose**: Assess the 7 core competencies of CBC.

**Components**:
- Core Competencies model
- Assessment per learning area
- Integration with subjects
- Reporting and analytics

**7 Core Competencies**:
1. **Communication and Collaboration**
2. **Critical Thinking and Problem Solving**
3. **Creativity and Imagination**
4. **Citizenship**
5. **Digital Literacy**
6. **Learning to Learn**
7. **Self-Efficacy**

### Phase 5: Multiple Assessment Methods

**Purpose**: Support various assessment methods as per CBC requirements.

**Components**:
- Assessment method tracking
- Oral assessments
- Practical assessments
- Written assessments
- Portfolio evidence

**Assessment Methods**:
- Written Tests
- Oral Presentations
- Practical Work
- Projects
- Portfolios
- Observations
- Self-Assessment
- Peer Assessment

### Phase 6: Enhanced Rubrics System

**Purpose**: Detailed competency-based rubrics with descriptors.

**Components**:
- Rubric templates
- Competency descriptors
- Performance indicators
- Integration with marks entry

**Rubric Structure**:
- Competency/Strand
- Sub-strand
- Performance indicators
- Descriptors for each level
- Evidence requirements

## Database Schema

### Performance Levels Table
```sql
- id
- code (E, M, A, B)
- name (Exceeding, Meeting, Approaching, Below)
- min_percentage
- max_percentage
- description
- color_code
- display_order
```

### Core Competencies Table
```sql
- id
- code
- name
- description
- learning_area_id (optional)
- display_order
```

### Portfolio Assessments Table
```sql
- id
- student_id
- subject_id
- classroom_id
- academic_year_id
- term_id
- portfolio_type
- title
- description
- evidence_files (JSON)
- rubric_scores (JSON)
- assessed_by
- assessment_date
- status
```

### Exam Marks Enhancements
```sql
- assessment_method (written, oral, practical, portfolio)
- competency_scores (JSON) - scores per competency
- performance_level_id
- portfolio_evidence_id (nullable)
- cat_number (for CATs)
```

### Exams Enhancements
```sql
- is_cat (boolean)
- cat_number (1, 2, 3)
- assessment_method
- competency_focus (JSON)
- portfolio_required (boolean)
```

## Implementation Priority

### High Priority (Must Have)
1. ✅ Performance Level Descriptors
2. ✅ CAT Tracking and Aggregation
3. ✅ Multiple Assessment Methods

### Medium Priority (Should Have)
4. Portfolio Assessments
5. Core Competencies Assessment
6. Enhanced Rubrics

### Low Priority (Nice to Have)
7. Learning Area Aggregation
8. Advanced Analytics

## Usage Examples

### Creating a CAT
```php
$cat = Exam::create([
    'name' => 'CAT 1',
    'type' => 'cat',
    'is_cat' => true,
    'cat_number' => 1,
    'assessment_method' => 'written',
    // ... other fields
]);
```

### Entering Marks with Performance Level
```php
$mark = ExamMark::create([
    'exam_id' => $cat->id,
    'student_id' => $student->id,
    'score_raw' => 75,
    'performance_level_id' => 2, // Meeting
    'assessment_method' => 'written',
    'competency_scores' => [
        'communication' => 4,
        'critical_thinking' => 3,
        // ...
    ]
]);
```

### Portfolio Assessment
```php
$portfolio = PortfolioAssessment::create([
    'student_id' => $student->id,
    'subject_id' => $subject->id,
    'portfolio_type' => 'project',
    'title' => 'Science Fair Project',
    'rubric_scores' => [
        'creativity' => 4,
        'presentation' => 3,
        // ...
    ]
]);
```

## Reporting Requirements

### Term Assessment Report
- Overall performance level per subject
- CAT breakdown
- Portfolio contributions
- Core competencies summary
- Learning area performance

### Transcript
- Performance levels for all subjects
- Core competencies assessment
- Portfolio highlights
- Overall competency profile

## Next Steps

1. Review and approve this implementation plan
2. Implement Phase 1 (Performance Levels)
3. Implement Phase 2 (CATs)
4. Test and refine
5. Implement remaining phases incrementally

## References

- Kenya CBC Curriculum Framework
- KICD Assessment Guidelines
- Competency-Based Assessment Best Practices

