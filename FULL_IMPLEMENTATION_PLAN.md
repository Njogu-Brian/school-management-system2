# Full Academic Module Implementation Plan

## Current Database Status
- 368 Students
- 12 Classrooms  
- 22 Subjects
- 4 Staff members

## Implementation Order

### Phase 1: Services (Foundation)
1. CBCAssessmentService - Calculate competencies, performance levels
2. TimetableService - Generate and manage timetables
3. SchemeOfWorkService - Generate schemes from strands

### Phase 2: Controllers with Authorization
1. SchemeOfWorkController - With teacher restrictions
2. LessonPlanController - With teacher restrictions
3. CBCStrandController - Admin only
4. PortfolioAssessmentController - With teacher restrictions
5. TimetableController - With proper access control

### Phase 3: Seeders
1. CBCStrandSeeder - Actual Kenyan CBC strands
2. CBCSubstrandSeeder - Actual Kenyan CBC substrands
3. TeacherAssignmentSeeder - Assign teachers to classes/subjects
4. SampleSchemeOfWorkSeeder - Sample schemes
5. SampleLessonPlanSeeder - Sample lesson plans

### Phase 4: Views
1. Schemes of work (index, create, edit, show)
2. Lesson plans (index, create, edit, show)
3. CBC strands management (admin)
4. Portfolio assessments
5. Timetable views

### Phase 5: Routes & Navigation
1. Add all routes with middleware
2. Update navigation menus
3. Add breadcrumbs

### Phase 6: Testing & Git
1. Test all features
2. Fix bugs
3. Commit to git

## Authorization Rules

### Teachers
- Can only access their assigned classes/subjects
- Can create/edit schemes and lesson plans for their classes
- Cannot delete or approve schemes
- Cannot manage CBC strands

### Admins
- Full access to everything
- Can approve schemes
- Can manage CBC strands/substrands

