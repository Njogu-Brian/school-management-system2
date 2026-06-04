# 04 — Role & Permission Audit

> Stack: `spatie/laravel-permission` (guard `web`, teams disabled) + custom `App\Http\Middleware\DirectorRoleMiddleware` aliased as `role`.
> **Headline finding:** there is **no single source of truth** for roles/permissions. Multiple overlapping seeders with different naming conventions create conflicting, run-order-dependent RBAC, several broad bypasses, and mobile/portal role mismatches.

---

## 1. Current roles (as seeded)

### Primary seeder — `RolesAndPermissionsSeeder.php`
`Super Admin`, `Director`, `Admin`, `Secretary`, `Academic Administrator`, `Deputy Senior Teacher`, `Teacher`, `Supervisor`, `Driver`, `Parent`, `Student`, `Accountant`.
*(Does NOT create `Senior Teacher` or `Finance Officer`; `Supervisor` & `Deputy Senior Teacher` get no permissions here.)*

### Default production seeder — `Comprehensive2025Seeder.php` (run by `DatabaseSeeder`)
`Super Admin`, `Admin`, `Secretary`, `Teacher`, `teacher`, `Accountant`, `Parent` — plus lowercase demo roles `teacher`, `administrator`, `accountant`, `secretary`, `chef`, `driver`, `janitor`, `security`.

### Other seeders (run manually / situationally)
- `AcademicPermissionsSeeder` → adds `Senior Teacher`, `Deputy Senior Teacher` (the real academics matrix; **not** in `DatabaseSeeder`).
- `SeniorTeacherPermissionsSeeder` → `Senior Teacher`.
- `ExpensePermissionSeeder` → `Finance Officer` (run by `DatabaseSeeder`).
- `AdminUserSeeder` → lowercase `admin`.
- `RoleSeeder` (legacy, **broken** — references non-existent `App\Models\Role`) → `admin`, `teacher`, `driver`, `janitor`, `accountant`, `receptionist`, `chef`, `security`.
- `DemoDataSeeder` → on-the-fly `bursar`.
- HR import (`StaffImport`) → `Teacher`, `Administrator`, `Staff` fallbacks.

### Roles referenced in code but not in main seeds
`Finance Officer`, `System Admin` (Gate bypass), `subject_lead`/`senior_teacher` (snake_case, `CurriculumDesignPolicy`), `Administrator`, `Staff`.

> **Net effect:** the live role set depends on *which seeders were run, in what order*. `syncPermissions` calls in `TeacherPermissionsSeeder`/`ExpensePermissionSeeder` can **overwrite** permissions set by earlier seeders.

---

## 2. Permissions (three+ parallel vocabularies)

Permissions use `module.action` dot notation, but with **inconsistent schemes**:

| Vocabulary | Source | Example |
|------------|--------|---------|
| Communication/ops | `PermissionSeeder` | `communication.send_sms`, `attendance.mark_attendance`, `settings.roles_permissions` |
| Coarse portal | `RolesAndPermissionsSeeder` | `students.view/create/edit/delete`, plus legacy `manage staff` |
| Dashboard-oriented | `Comprehensive2025Seeder` | `dashboard.view`, `students.manage`, `finance.manage` |
| Full academics RBAC | `AcademicPermissionsSeeder` | `schemes_of_work.approve/publish/export_pdf`, `exams.enter_marks/publish/approve`, `homework.assign/mark/submit/approve`, `curriculum_assistant.use` |
| Teacher subset | `TeacherPermissionsSeeder` | `report_card_skills.edit`, `diaries.create` |
| Senior teacher | `SeniorTeacherPermissionsSeeder` | `senior_teacher.supervisory_classes.view`, `finance.fee_balances.view` |
| Expenses | `ExpensePermissionSeeder` | `expense.create/submit/approve/pay`, `voucher.manage`, `vendor.manage` |

**Mobile** (`mobile-app/src/constants/roles.ts`) defines its **own** snake_case `PERMISSIONS` constants that are **not wired** to Spatie — the API returns Spatie permission names from `$user->getAllPermissions()`.

---

## 3. Role → permission assignment

| Seeder | Method | Summary |
|--------|--------|---------|
| RolesAndPermissionsSeeder | `syncPermissions` | Super Admin + Director → ALL; others → curated subsets; Supervisor/Deputy → none |
| Comprehensive2025Seeder | `syncPermissions` | Super Admin/Admin → full coarse; rest subsets |
| AcademicPermissionsSeeder | `givePermissionTo` (additive) | Teacher/Senior/Deputy/Admin/Secretary/Parent/Student/Super Admin |
| TeacherPermissionsSeeder | `syncPermissions` (overwrites Admin/Secretary with ALL) | conflict risk |
| ExpensePermissionSeeder | `syncPermissions` | Finance Officer/Accountant/Admin/Super Admin → all expense perms |
| AdminUserSeeder | `syncPermissions(Permission::all())` on `admin` | |

---

## 4. Enforcement model

### Middleware (`bootstrap/app.php`)
```
'role'                => App\Http\Middleware\DirectorRoleMiddleware  // NOT raw Spatie
'permission'          => Spatie PermissionMiddleware                 // defined, UNUSED on web routes
'role_or_permission'  => Spatie RoleOrPermissionMiddleware           // defined, unused
'user-access'         => App\Http\Middleware\UserAccess
```

### `DirectorRoleMiddleware` (the real gate)
- Role **`Director`** → bypasses **all** role checks.
- Routes listing teaching roles also admit `hasTeacherLikeRole()` **or** `hasTeachingAssignments()` (HR-assignment fallback).
- Otherwise delegates to Spatie role matching.

### Where authorization actually lives
- **Web routes:** only `role:` middleware (piped OR lists). **No `permission:`/`can:` on web routes.**
- **Controllers:** `$this->middleware('permission:...')`, `hasRole()`, `can()`, `hasPermissionTo()` — with **duplicate casing checks** (`Teacher` vs `teacher`).
- **Blade:** `@can(...)`, `@canAccess` / `can_access()` helper.
- **Policies:** `PaymentPolicy`, `ExpensePolicy`, `BankStatementTransactionPolicy`, `CurriculumDesignPolicy`.
- **API:** `AuthApiController::formatUserForApi()` returns first role name + all permission names.

---

## 5. Hidden / implicit permissions (the bypass map)

| Mechanism | Location | Effect |
|-----------|----------|--------|
| `Gate::before` superuser | `AuthServiceProvider` | `Super Admin`, `Admin`, `System Admin` → **all** abilities return true |
| Teacher academic `Gate::before` | `AppServiceProvider` | any teacher-like user / user with teaching assignments → all `TeacherAcademicPermissions` |
| `can_access()` helper | `app/helpers.php` | Super Admin always true; teacher-like always true; teaching-assignment always true; else `$user->can()` |
| Director route bypass | `DirectorRoleMiddleware` | `Director` skips all role middleware |
| Hardcoded role checks | `BackupRestoreController` (`Super Admin` only), others | bespoke gating outside RBAC |
| `is_supervisor()` (HR) | `app/helpers.php` | checks **staff subordinates**, *not* the Spatie `Supervisor` role — different concepts sharing a name |
| `ensureCriticalPermissions()` | `AppServiceProvider` | bootstraps curriculum perms at runtime |

**Consequence:** effective permissions are far broader than the seeded matrix suggests; Super Admin/Admin and "teacher-like" users are de-facto superusers for large parts of the app. There is **no real role hierarchy** in Spatie config — hierarchy is implicit via these bypasses.

---

## 6. Mobile ↔ portal role mapping

`normalizeRole()` (mobile) lowercases the API role string and maps it:

| Mobile `UserRole` | Backend role | Status |
|-------------------|--------------|--------|
| `super_admin` | `Super Admin` | OK |
| `admin` | `Admin` / legacy `admin` | ⚠️ case split breaks some middleware |
| `secretary` | `Secretary` | OK |
| `academic_admin` | `Academic Administrator` | OK |
| `teacher` | `Teacher`/`teacher` | OK (dual roles) |
| `senior_teacher` | `Senior Teacher` | OK *only if* academic seeders were run |
| `supervisor` | `Supervisor` | ⚠️ often no permissions; overlaps HR `is_supervisor()` |
| `accountant` | `Accountant` | OK |
| `finance` | `Finance Officer` | ❌ name mismatch |
| `parent` | `Parent` | OK |
| `guardian` | — | ❌ no Spatie role (guardian is a family field) |
| `student` | `Student` | OK |
| `driver` | `Driver` | OK |
| `transport` | — | ❌ no Spatie role (`transport.*` is a route, not a role) |

---

## 7. Missing roles vs full school org structure

| Org role (requested) | Present? | Notes |
|----------------------|----------|-------|
| Board Member | ❌ | governance role missing |
| Director | ✅ | route bypass |
| Principal | ❌ | — |
| Deputy Principal | ❌ | `Deputy Senior Teacher` ≠ principal |
| Head Teacher | ❌ | closest = `Senior Teacher` |
| Academic Director | ⚠️ | `Academic Administrator` |
| Finance Director | ❌ | closest = `Finance Officer` |
| Bursar | ⚠️ demo-only | `bursar` in `DemoDataSeeder` |
| Accountant | ✅ | |
| Secretary | ✅ | |
| Receptionist | ⚠️ legacy/broken | `RoleSeeder` only |
| Teacher | ✅ | (+ lowercase) |
| Senior Teacher | ✅* | needs academic seeders |
| Class Teacher | ❌ role | `class_teacher_assignments` table instead |
| Subject Teacher | ❌ role | Teacher + assignments |
| Parent | ✅ | |
| Student | ✅ | |
| Driver | ✅ | |
| Transport Manager | ❌ | — |
| Nurse | ❌ | — |
| Librarian | ❌ | library uses Admin/Teacher |
| Store Keeper | ❌ | inventory uses Admin/Secretary/Teacher |
| Security Officer | ⚠️ demo | lowercase `security` |
| HR Officer | ❌ | HR gated by Admin-type roles |

Also present but not in the org list: `Supervisor`, `Chef`, `Janitor`, `Administrator`, `Staff`, `subject_lead`.

---

## 8. Risks & recommendations

### Risks
1. **Run-order-dependent RBAC** → unpredictable permissions per deployment.
2. **Case-duplicate roles** (`Admin`/`admin`, `Teacher`/`teacher`) silently bypass or block middleware.
3. **Broad Gate bypasses** make least-privilege impossible; an over-broad `hasTeacherLikeRole()` grants academic powers widely.
4. **Mobile mismatches** (`guardian`, `finance`, `transport`) → roles that don't authorize correctly server-side; `normalizeRole` defaulting unknown → `teacher` (client side) compounds this.
5. **Missing operational roles** force everything through Admin/Secretary, defeating segregation of duties (critical in finance).

### Recommendations (for future state)
1. **One canonical role taxonomy** seeded by a single idempotent `RolesAndPermissionsSeeder`; deprecate all others. Title Case only; remove lowercase duplicates.
2. **Permission-first authorization:** define a complete `module.action` permission catalog; assign to roles; enforce with `permission:` middleware + policies. Reserve `role:` for coarse routing only.
3. **Introduce the missing roles** (Principal, Deputy Principal, Head Teacher, Academic Director, Finance Director, Bursar, Receptionist, Transport Manager, Nurse, Librarian, Store Keeper, Security Officer, HR Officer, Board Member) with scoped permission sets.
4. **Replace broad `Gate::before` bypasses** with explicit super-admin-only bypass + real permission checks elsewhere.
5. **Model `guardian` as a relationship, not a role**; make `finance`/`transport` map to `Finance Officer`/`Transport Manager` consistently across API + mobile.
6. **Add role hierarchy/scoping** (campus-scoped, class-scoped) for senior teachers/HoDs — currently approximated via `campus_senior_teachers`/assignment tables.
7. **Audit & test:** add automated tests asserting each role's effective permissions; surface a "who can do what" matrix in the Admin App.
