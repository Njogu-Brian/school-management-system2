# Remaining Implementation Guide

## Status: Foundation Complete ✅

### Completed:
- ✅ All migrations
- ✅ All models
- ✅ Services (CBCAssessmentService, TimetableService)
- ✅ SchemeOfWorkController
- ✅ Permissions seeded
- ✅ Performance levels and competencies seeded

### Remaining Controllers to Implement:

1. **LessonPlanController** - Similar to SchemeOfWorkController
2. **CBCStrandController** - Admin only, CRUD
3. **PortfolioAssessmentController** - With teacher restrictions
4. **TimetableController** - Generate/view timetables

### Routes to Add:
Add to routes/web.php in academics group (around line 276):

```php
// Schemes of Work
Route::resource('schemes-of-work', SchemeOfWorkController::class)->parameters(['schemes-of-work' => 'schemes_of_work']);
Route::post('schemes-of-work/{schemes_of_work}/approve', [SchemeOfWorkController::class, 'approve'])->name('schemes-of-work.approve');

// Lesson Plans
Route::resource('lesson-plans', LessonPlanController::class)->parameters(['lesson-plans' => 'lesson_plan']);

// CBC Strands (Admin only)
Route::middleware('role:Super Admin|Admin')->group(function() {
    Route::resource('cbc-strands', CBCStrandController::class)->parameters(['cbc-strands' => 'cbc_strand']);
    Route::get('cbc-strands/{cbc_strand}/substrands', [CBCStrandController::class, 'substrands'])->name('cbc-strands.substrands');
});

// Portfolio Assessments
Route::resource('portfolio-assessments', PortfolioAssessmentController::class)->parameters(['portfolio-assessments' => 'portfolio_assessment']);

// Timetable
Route::get('timetable', [TimetableController::class, 'index'])->name('timetable.index');
Route::get('timetable/classroom/{classroom}', [TimetableController::class, 'classroom'])->name('timetable.classroom');
Route::get('timetable/teacher/{teacher}', [TimetableController::class, 'teacher'])->name('timetable.teacher');
Route::post('timetable/generate', [TimetableController::class, 'generate'])->name('timetable.generate');
Route::post('timetable/save', [TimetableController::class, 'save'])->name('timetable.save');
```

### Navigation Updates:
Add to nav-admin.blade.php in the Academics section:

```php
{{-- Schemes of Work --}}
<a href="{{ route('academics.schemes-of-work.index') }}" class="sublink {{ Request::is('academics/schemes-of-work*') ? 'active' : '' }}">
    <i class="bi bi-journal-text"></i> Schemes of Work
</a>

{{-- Lesson Plans --}}
<a href="{{ route('academics.lesson-plans.index') }}" class="sublink {{ Request::is('academics/lesson-plans*') ? 'active' : '' }}">
    <i class="bi bi-calendar-check"></i> Lesson Plans
</a>

{{-- Portfolio Assessments --}}
<a href="{{ route('academics.portfolio-assessments.index') }}" class="sublink {{ Request::is('academics/portfolio-assessments*') ? 'active' : '' }}">
    <i class="bi bi-folder"></i> Portfolio Assessments
</a>

{{-- Timetable --}}
<a href="{{ route('academics.timetable.index') }}" class="sublink {{ Request::is('academics/timetable*') ? 'active' : '' }}">
    <i class="bi bi-calendar-week"></i> Timetable
</a>

{{-- CBC Strands (Admin only) --}}
@if(auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
<a href="{{ route('academics.cbc-strands.index') }}" class="sublink {{ Request::is('academics/cbc-strands*') ? 'active' : '' }}">
    <i class="bi bi-diagram-3"></i> CBC Strands
</a>
@endif
```

## Implementation Priority

1. Complete all controllers (following SchemeOfWorkController pattern)
2. Add routes
3. Create basic views (index, create, edit, show)
4. Update navigation
5. Create CBC substrand seeder
6. Test everything
7. Commit to git

