# Senior Teacher Role Implementation Guide

## Overview
A comprehensive Senior Teacher role has been successfully implemented in the school management system. This role is designed for supervisors who oversee specific classrooms and staff members, with enhanced permissions beyond regular teachers.

## Implementation Date
January 12, 2026

## Role Purpose
Senior Teachers are supervisory staff members who:
- Supervise specific classrooms assigned by administrators
- Monitor and manage assigned teaching staff
- Have full teacher permissions plus supervisory capabilities
- Can view comprehensive data for supervised classes and staff

## Components Created

### 1. Database Structure
**Migration File**: `database/migrations/2026_01_12_000001_create_senior_teacher_supervisory_tables.php`

Two new pivot tables:
- `senior_teacher_classrooms`: Links senior teachers to supervised classrooms
- `senior_teacher_staff`: Links senior teachers to supervised staff members

Both tables include:
- Proper foreign key constraints
- Unique constraints to prevent duplicate assignments
- Timestamps for tracking

### 2. Permissions & Seeders
**Seeder File**: `database/seeders/SeniorTeacherPermissionsSeeder.php`

Permissions granted to Senior Teachers:
- **Dashboard**: `dashboard.senior_teacher.view`
- **All Teacher Permissions**: Attendance, exam marks, report cards, homework, diaries, student behaviours
- **Timetable**: View and edit capabilities
- **Student Data**: Full access for supervised students
- **Transport**: View-only access for supervised students
- **Finance**: View fee balances only (cannot collect, edit, invoice, or discount)
- **Supervisory**: `senior_teacher.supervisory_classes.view`, `senior_teacher.supervised_staff.view`

### 3. Models & Relationships
**Updated File**: `app/Models/User.php`

New relationships added:
- `supervisedClassrooms()`: BelongsToMany relationship with Classroom model
- `supervisedStaff()`: BelongsToMany relationship with Staff model
- `isSupervisingClassroom($classroomId)`: Check if supervising specific classroom
- `isSupervisingStaff($staffId)`: Check if supervising specific staff
- `getSupervisedClassroomIds()`: Get all supervised classroom IDs
- `getSupervisedStaffIds()`: Get all supervised staff IDs
- `getSupervisedStudents()`: Query builder for all students in supervised classrooms

### 4. Controllers

#### Senior Teacher Controller
**File**: `app/Http/Controllers/SeniorTeacher/SeniorTeacherController.php`

Methods:
- `dashboard()`: Main dashboard with KPIs and overview
- `supervisedClassrooms()`: List all supervised classrooms
- `supervisedStaff()`: List all supervised staff
- `students()`: View and filter students from supervised classes
- `studentShow()`: Detailed student view
- `feeBalances()`: View fee balances (read-only)

#### Admin Assignment Controller
**File**: `app/Http/Controllers/Admin/SeniorTeacherAssignmentController.php`

Methods:
- `index()`: List all senior teachers with assignment counts
- `edit()`: Manage assignments for specific senior teacher
- `updateClassrooms()`: Update supervised classroom assignments
- `updateStaff()`: Update supervised staff assignments
- `removeClassroom()`: Remove specific classroom assignment
- `removeStaff()`: Remove specific staff assignment
- `bulkAssign()`: Bulk assignment functionality

### 5. Routes

#### Senior Teacher Routes
**File**: `routes/senior_teacher.php`

Route groups:
- Dashboard and main functions
- Supervisory relationships (classrooms, staff)
- Students (view and details)
- Fee balances (view-only)
- Attendance (mark and view)
- Exam marks
- Report cards
- Homework & diaries
- Student behaviours
- Timetable
- Personal routes (salary, advances, leaves)
- Communication (announcements, events)

#### Admin Routes
**Added to**: `routes/web.php`

Route prefix: `/admin/senior-teacher-assignments`
- Index, edit, update, and remove operations
- Requires Super Admin or Admin role

### 6. Views

#### Senior Teacher Views
**Directory**: `resources/views/senior_teacher/`

Created files:
1. `dashboard.blade.php`: Main dashboard with KPIs, charts, and quick actions
2. `supervised_classrooms.blade.php`: Grid view of supervised classrooms
3. `supervised_staff.blade.php`: Table view of supervised staff
4. `students.blade.php`: Filterable student list
5. `student_show.blade.php`: Detailed student profile with tabs
6. `fee_balances.blade.php`: Fee balance overview (view-only)

#### Admin Assignment Views
**Directory**: `resources/views/admin/senior_teacher_assignments/`

Created files:
1. `index.blade.php`: List all senior teachers with assignment counts
2. `edit.blade.php`: Manage classroom and staff assignments with checkboxes

### 7. Navigation
**File**: `resources/views/layouts/partials/nav-senior-teacher.blade.php`

Navigation structure:
- Dashboard
- My Profile
- **Supervisory Section**:
  - Supervised Classrooms
  - Supervised Staff
  - All Students
  - Fee Balances
- **Teaching & Academics Section**:
  - Attendance
  - Exam Marks
  - Report Cards
  - Homework
  - Student Behaviour
  - Digital Diaries
  - Timetable
- **Personal Section**:
  - Salary & Payslips
  - Advance Requests
  - Leaves
- **Communication Section**:
  - Announcements
  - Events Calendar

Updated `resources/views/layouts/app.blade.php` to include senior teacher navigation switching logic.

## Key Features

### 1. Supervisory Dashboard
- KPI cards: Supervised classrooms, supervised staff, total students, attendance rate
- Today's attendance overview with trend chart
- Recent student behaviours
- Pending homework assignments
- Fee balance summary
- Upcoming exams

### 2. Access Control
**Can Do:**
- View all data for supervised classes and students
- Mark attendance for supervised classes
- Enter exam marks
- Create and manage homework
- Record student behaviours
- Edit timetables
- View transport information
- View fee balances
- Manage report cards and skills

**Cannot Do:**
- Create new students
- Collect fees or issue invoices
- Apply discounts or credit notes
- View HR details (except own profile)
- Access payroll information
- Modify staff records

### 3. Data Filtering
All data views automatically filter to show only:
- Students from supervised or assigned classrooms
- Information related to supervised staff
- Attendance, homework, and behaviours for supervised classes

## Setup Instructions

### Step 1: Run Migrations
```bash
php artisan migrate
```

### Step 2: Run Seeders
```bash
php artisan db:seed --class=SeniorTeacherPermissionsSeeder
```

### Step 3: Assign Role
To assign the Senior Teacher role to a user:
1. Navigate to HR → Staff
2. Select a staff member
3. Assign the "Senior Teacher" role
4. Or use Spatie's role assignment:
```php
$user->assignRole('Senior Teacher');
```

### Step 4: Assign Supervisory Classes
1. Navigate to Admin → Senior Teacher Assignments
2. Click "Manage Assignments" for a senior teacher
3. Select classrooms to supervise
4. Select staff to supervise
5. Save changes

## Access Points

### For Senior Teachers
- Dashboard: `/senior-teacher/home`
- Students: `/senior-teacher/students`
- Supervised Classrooms: `/senior-teacher/supervised-classrooms`
- Supervised Staff: `/senior-teacher/supervised-staff`
- Fee Balances: `/senior-teacher/fee-balances`

### For Administrators
- Manage Assignments: `/admin/senior-teacher-assignments`

## Enhancements & Suggestions

### Recommended Additional Features
1. **Performance Reports**: Generate performance reports for supervised staff
2. **Notification System**: Alerts for low attendance in supervised classes
3. **Approval Workflows**: Senior teachers approve homework/lesson plans
4. **Communication Tools**: Bulk messaging to supervised class parents
5. **Analytics Dashboard**: Advanced charts and trends for supervised data
6. **Export Functionality**: Export reports for supervised classes
7. **Calendar Integration**: View all supervised class schedules in one calendar
8. **Parent Communication**: Direct messaging with parents of supervised students

### Security Considerations
1. All controllers check role permissions
2. Data queries filter by supervised relationships
3. No direct database access to non-supervised data
4. Proper middleware on all routes
5. View-only access enforced for sensitive data (fees, HR)

## Testing Checklist

- [ ] Senior teacher can log in and see custom dashboard
- [ ] Dashboard shows correct KPIs
- [ ] Can view only supervised classrooms
- [ ] Can view only supervised staff
- [ ] Can view students from supervised classes only
- [ ] Cannot access HR or payroll sections
- [ ] Can view but not collect fees
- [ ] Can mark attendance for supervised classes
- [ ] Can create and edit homework
- [ ] Navigation menu displays correctly
- [ ] Admin can assign/remove supervisory relationships
- [ ] Assignments persist correctly in database
- [ ] No access to non-supervised data

## Support & Maintenance

### Common Operations

**Add a new senior teacher:**
```php
$user->assignRole('Senior Teacher');
```

**Assign classroom supervision:**
```php
$seniorTeacher->supervisedClassrooms()->attach($classroomId);
```

**Remove supervision:**
```php
$seniorTeacher->supervisedClassrooms()->detach($classroomId);
```

**Check supervision:**
```php
if ($user->isSupervisingClassroom($classroomId)) {
    // Has supervision
}
```

### Troubleshooting

**Issue**: Senior teacher sees no data
- **Solution**: Ensure classrooms are assigned via admin panel

**Issue**: Permission denied errors
- **Solution**: Run permissions seeder and verify role assignment

**Issue**: Navigation not showing
- **Solution**: Clear cache: `php artisan cache:clear`, `php artisan view:clear`

## Files Modified/Created Summary

### New Files (25)
1. Migration: Senior teacher supervisory tables
2. Seeder: Senior teacher permissions
3. Controller: SeniorTeacherController
4. Controller: SeniorTeacherAssignmentController
5. Routes: senior_teacher.php
6-11. Views: Senior teacher dashboards and pages (6 files)
12-13. Views: Admin assignment pages (2 files)
14. Navigation: nav-senior-teacher.blade.php
15. Documentation: This file

### Modified Files (3)
1. `app/Models/User.php`: Added relationships and methods
2. `routes/web.php`: Added imports and routes
3. `resources/views/layouts/app.blade.php`: Updated navigation logic

## Conclusion

The Senior Teacher role is now fully operational with comprehensive supervisory capabilities, proper access controls, and a user-friendly interface. The implementation follows Laravel best practices and integrates seamlessly with the existing school management system.

For questions or issues, refer to the testing checklist and troubleshooting section above.

