# Manual Testing Guide — Teacher, Senior Teacher (Campus Leader), Super Admin

Use this guide to test the system as **Teacher**, **Senior Teacher / Campus Leader**, and **Super Admin**. Perform each section with the corresponding role.

---

## Prerequisites

- Application running locally (e.g. `php artisan serve` or your usual setup).
- At least one user per role:
  - **Super Admin**
  - **Admin** (optional; can use Super Admin for admin tests)
  - **Teacher**
  - **Senior Teacher** (with a **campus assignment** in Admin → HR → Senior Teacher Assignments)
- Some seed data: classrooms (with `campus` set where relevant), subjects, staff, students, so that reports and assessments have options.

---

## Part 1 — Teacher

Log in as a user with the **Teacher** role.

### 1.1 Dashboard & navigation

| Step | Action | Expected |
|------|--------|----------|
| 1 | Log in as Teacher. | Redirect to Teacher Dashboard. |
| 2 | Check sidebar. | See: Dashboard, My Profile, My Students, Attendance, Swimming, Exam Marks, **Assessments**, **Weekly Reports** (collapse), Report Cards, Homework, Student Behaviour, Digital Diaries, Timetable, etc. |
| 3 | Open **Weekly Reports** in the sidebar. | Submenu: Class Reports, Subject Reports, Staff Weekly, Student Followups, Operations & Facilities. |
| 4 | Click **Assessments**. | Go to `/academics/assessments` — list of assessments (or empty). Page uses settings-style layout (header, card, table). |

### 1.2 Assessments (Teacher)

| Step | Action | Expected |
|------|--------|----------|
| 1 | From Assessments list, click **New Assessment**. | Create form with: Assessment Date, Week Ending, Class, Subject, Student, Assessment Type, Score, Out Of, Teacher, Academic Group, Remarks. |
| 2 | Fill required fields and submit. | Success message; redirect to assessments list; new row appears. |
| 3 | Use **Filter** by Week Ending. | List filters by that week (if backend supports it). |

### 1.3 Weekly reports (Teacher)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Sidebar → **Weekly Reports** → **Class Reports**. | List at `/weekly-reports/class-reports`; option to add **New Report**. |
| 2 | Create a class report (week ending, campus, class, teacher, etc.). | Saves; appears in list. |
| 3 | Repeat for **Subject Reports**, **Staff Weekly**, **Student Followups**, **Operations & Facilities**. | Each opens index and create form; submit saves and shows in list. |
| 4 | Use **Filter** by week ending where available. | List filtered by date. |

### 1.4 Teacher-only restrictions

| Step | Action | Expected |
|------|--------|----------|
| 1 | Try to open `/admin/senior-teacher-assignments`. | 403 or redirect (no access). |
| 2 | Try to open `/reports/heatmaps/lower` (if Teacher has no Senior Teacher role). | 403 or redirect (heatmaps are Admin / Senior Teacher / Director). |

---

## Part 2 — Senior Teacher (Campus Leader)

Log in as a user with the **Senior Teacher** role, and ensure that user has **one campus assigned** (Lower or Upper) in Super Admin → HR → Senior Teacher Assignments.

### 2.1 Dashboard & navigation

| Step | Action | Expected |
|------|--------|----------|
| 1 | Log in as Senior Teacher. | Redirect to Senior Teacher Dashboard. |
| 2 | Check sidebar. | Sections: Supervisory (Supervised Classrooms, Supervised Staff, All Students, Fee Balances, Leave Approval), Teaching & Academics (includes **Assessments**, Attendance, Exam Marks, Report Cards, Homework, etc.), **Reports** (with **Campus & Weekly Reports** and HR Reports), Personal, Transport, Inventory, Communication. |
| 3 | Open **Campus & Weekly Reports** under Reports. | Submenu: Heatmap – Lower, Heatmap – Upper, Class Reports, Subject Reports, Staff Weekly, Student Followups, Operations & Facilities. |

### 2.2 Campus heatmap (Senior Teacher)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Click **Heatmap – Lower** (if assigned to **Lower** campus). | Page loads with Lower campus heatmap (class × subject averages). |
| 2 | Click **Heatmap – Upper** (if assigned to **Upper** campus). | Page loads with Upper campus heatmap. |
| 3 | If you are assigned to **Lower** only, open **Heatmap – Upper** (or vice versa). | 403 “You do not have access to this campus heatmap.” |
| 4 | Use **Filter** by week ending. | Heatmap data updates for that week (if data exists). |

### 2.3 Supervised scope (campus-based)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Go to **Supervised Classrooms**. | List of classrooms for your **assigned campus** only (no separate classroom assignment). |
| 2 | Go to **Supervised Staff**. | List of staff who teach/work in that campus (derived from campus). |
| 3 | Go to **All Students**. | Students in classrooms belonging to your campus. |
| 4 | Go to **Fee Balances**. | Fee balances for students in your campus only. |

### 2.4 Assessments & weekly reports (Senior Teacher)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Sidebar → **Assessments** (under Teaching & Academics). | Same assessments list/create as Teacher; can add and filter. |
| 2 | **Campus & Weekly Reports** → Class Reports, Subject Reports, Staff Weekly, Student Followups, Operations & Facilities. | Each index and create form works; styling matches rest of app. |

### 2.5 Senior Teacher restrictions

| Step | Action | Expected |
|------|--------|----------|
| 1 | Try to open `/admin/senior-teacher-assignments`. | 403 or redirect (Admin/Super Admin only). |

---

## Part 3 — Super Admin

Log in as a user with the **Super Admin** role.

### 3.1 Dashboard & navigation

| Step | Action | Expected |
|------|--------|----------|
| 1 | Log in as Super Admin. | Redirect to Admin Dashboard. |
| 2 | Check sidebar. | Full admin menu: Dashboards, Students, Attendance, Academics, CBC Curriculum, Timetable, Homework & Diaries, Exams, Report Cards, **Assessments**, **Campus & Weekly Reports** (Heatmaps + weekly reports), Behaviours, Finance, Transport, Communication, HR, Payroll, Events, Inventory, POS, Documents, Settings. |
| 3 | Open **Campus & Weekly Reports**. | Submenu: Heatmap – Lower, Heatmap – Upper, Class Reports, Subject Reports, Staff Weekly, Student Followups, Operations & Facilities. |

### 3.2 Senior Teacher assignments (campus-only)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Go to **HR** → **Senior Teacher Assignments**. | List of Senior Teachers with **Campus** column only (no “Supervised Classrooms” or “Supervised Staff” columns). |
| 2 | Click **Manage** for a Senior Teacher. | Edit page shows only **Campus Assignment** (dropdown: Lower / Upper) and **Save Campus**. No classroom or staff assignment sections. |
| 3 | Change campus and save. | Success message; list shows updated campus. Only one Senior Teacher per campus. |
| 4 | Assign the other campus to another Senior Teacher. | Both campuses show one Senior Teacher each. |

### 3.3 Heatmaps (Super Admin)

| Step | Action | Expected |
|------|--------|----------|
| 1 | **Campus & Weekly Reports** → **Heatmap – Lower**. | Lower campus heatmap (class × subject averages). |
| 2 | **Heatmap – Upper**. | Upper campus heatmap. |
| 3 | Filter by week ending. | Data updates for selected week. |

### 3.4 Assessments (Super Admin)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Sidebar → **Assessments**. | List at `/academics/assessments`. |
| 2 | **New Assessment**; fill and save. | New assessment appears in list. |
| 3 | Filter by week ending. | List filtered. |

### 3.5 Weekly reports (Super Admin)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Open each: **Class Reports**, **Subject Reports**, **Staff Weekly**, **Student Followups**, **Operations & Facilities**. | Each index page loads with consistent settings-style layout. |
| 2 | For each, click **New Report** / **New Follow-Up** and submit a valid form. | Success; new row in list. |
| 3 | Use **Filter** by week ending where shown. | Lists filter correctly. |

### 3.6 Styling and layout (all roles)

| Step | Action | Expected |
|------|--------|----------|
| 1 | On any of: Assessments (index/create), Heatmaps, Class/Subject/Staff Weekly/Student Followups/Operations (index/create). | Same visual pattern: gradient page header (crumb, title, subtitle), settings-card, table-modern or form with btn-settings-primary / btn-ghost-strong, input-chip for counts where relevant. |
| 2 | Check alerts (e.g. after save). | Success alerts are dismissible. |

### 3.7 Other admin areas (smoke check)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Open **Students**, **Academics** → Classrooms, **Finance** (e.g. Invoices), **HR** → Staff. | Pages load without errors. |
| 2 | Log out and log in as Teacher, then Senior Teacher. | Each role sees correct sidebar and dashboards. |

---

## Automated tests (optional)

- Run the suite: `php artisan test` (no parallel) or `./vendor/bin/phpunit`.
- Some tests may fail if the test environment (e.g. `.env.testing`, database) is not configured; failures in HelpersTest, StudentModelTest, Finance tests, etc. are often environment-related rather than from the new features.
- The new behaviour (Senior Teacher campus-only assignment, heatmaps, assessments, weekly reports) does not yet have dedicated automated tests; this guide covers **manual** verification.

---

## Quick reference — who can access what

| Feature | Teacher | Senior Teacher | Super Admin |
|--------|---------|-----------------|-------------|
| Assessments (list/create) | Yes | Yes | Yes |
| Weekly Reports (all 5 types) | Yes | Yes | Yes |
| Heatmap – Lower/Upper | No | Yes (own campus only) | Yes (both) |
| Supervised Classrooms/Staff/Students (campus-based) | No | Yes | N/A (admin sees all) |
| Senior Teacher Assignments (assign campus) | No | No | Yes |
| HR, Finance, full Students, Settings | No | Limited (e.g. HR Reports) | Yes |

---

## If something fails

1. **403 on heatmap**  
   Senior Teacher must have a campus assigned (Super Admin → HR → Senior Teacher Assignments). They can only open the heatmap for that campus.

2. **Empty supervised classrooms/staff**  
   Ensure classrooms have `campus` (or appropriate `level_type`) set so that `Classroom::forCampus($campus)` returns rows. Senior Teacher scope is entirely derived from their assigned campus.

3. **Routes or nav missing**  
   Clear config and route caches: `php artisan config:clear` and `php artisan route:clear`. Confirm roles (e.g. “Senior Teacher”, “Teacher”) match the middleware and nav conditions.

4. **Styling looks different**  
   Ensure `resources/views/settings/partials/styles.blade.php` is present and included in the blade (e.g. `@push('styles') @include('settings.partials.styles') @endpush`).

Use this guide end-to-end for **Teacher**, **Senior Teacher**, and **Super Admin** to confirm no errors and that behaviour matches the table above.
