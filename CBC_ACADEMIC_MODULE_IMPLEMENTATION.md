# CBC Academic Module - Comprehensive Implementation Guide

## Overview
This document outlines the complete CBC-compliant academic module implementation including strands/substrands, schemes of work, lesson plans, and enhanced report cards.

## Database Structure

### 1. CBC Strands & Substrands
- **cbc_strands**: Learning area strands (e.g., LA1 - Listening and Speaking)
- **cbc_substrands**: Specific learning units within strands

### 2. Schemes of Work
- **schemes_of_work**: Term-based teaching plans linked to subjects, classrooms, and CBC strands

### 3. Lesson Plans
- **lesson_plans**: Daily/weekly lesson plans with CBC integration

### 4. Enhanced Report Cards
- CBC performance levels
- Core competencies assessment
- Learning areas performance
- CAT breakdown
- Portfolio evidence
- Co-curricular activities
- Personal & social development

## Roles & Permissions

### Teacher Permissions
- `schemes_of_work.view` - View schemes of work
- `schemes_of_work.create` - Create schemes (own classes only)
- `schemes_of_work.edit` - Edit own schemes
- `lesson_plans.view` - View lesson plans
- `lesson_plans.create` - Create lesson plans (own classes only)
- `lesson_plans.edit` - Edit own lesson plans
- `report_cards.view` - View report cards (own classes only)
- `report_cards.skills.edit` - Edit skills/competencies (own classes only)
- `report_cards.remarks.edit` - Add remarks (own classes only)
- `cbc_strands.view` - View CBC strands/substrands
- `portfolio_assessments.view` - View portfolios
- `portfolio_assessments.create` - Create portfolios (own classes only)

### Admin/Secretary Permissions
- Full access to all academic modules
- Can approve schemes of work
- Can publish report cards
- Can manage CBC strands/substrands

## Implementation Checklist

### Phase 1: Database & Models ✅
- [x] Create migrations for all tables
- [ ] Create and populate models
- [ ] Create relationships
- [ ] Create seeders with sample data

### Phase 2: Controllers & Authorization
- [ ] Create SchemeOfWorkController with authorization
- [ ] Create LessonPlanController with authorization
- [ ] Create CBCStrandController (admin only)
- [ ] Enhance ReportCardController with CBC features
- [ ] Create PortfolioAssessmentController with authorization

### Phase 3: Views
- [ ] Schemes of work management interface
- [ ] Lesson planning interface
- [ ] Enhanced report card views
- [ ] CBC strand/substrand management (admin)
- [ ] Portfolio assessment interface

### Phase 4: Services
- [ ] CBCAssessmentService - Calculate competencies
- [ ] ReportCardGenerationService - Enhanced CBC reports
- [ ] SchemeOfWorkService - Generate from strands
- [ ] LessonPlanService - Auto-populate from schemes

### Phase 5: Reporting
- [ ] CBC-compliant report card PDF
- [ ] Learning area performance reports
- [ ] Competency tracking reports
- [ ] Scheme of work coverage reports

## Key Features

### 1. Schemes of Work
- Link to CBC strands and substrands
- Track coverage of curriculum
- Term-based planning
- Approval workflow
- Progress tracking

### 2. Lesson Plans
- Link to substrands
- Core competencies integration
- Learning resources tracking
- Assessment methods
- Reflection and improvement notes

### 3. Report Cards
- Performance levels (E, M, A, B)
- Core competencies assessment
- Learning areas summary
- CAT breakdown
- Portfolio evidence
- Co-curricular activities
- Personal & social development
- Student self-assessment
- Parent feedback section

### 4. Authorization
- Teachers can only access their assigned classes/subjects
- Admins have full access
- Proper gate checks for all operations
- Audit trails for changes

## Next Steps

1. Run migrations
2. Seed CBC strands and substrands
3. Create models with relationships
4. Implement controllers with authorization
5. Create views
6. Test permissions
7. Generate sample data

