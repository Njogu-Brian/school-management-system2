# Live inventory audit — 22 September 2026

This is a **file-level audit of the current tree**, not a product-strategy rewrite. Every Blade view, HTTP controller, Eloquent model, and markdown document was counted and cross-referenced on this date.

Companion catalogs (complete lists):

| Catalog | What it contains |
|---|---|
| [11-blade-catalog.md](./11-blade-catalog.md) | All **861** Blade files, grouped by folder, with inferred purpose |
| [12-controller-catalog.md](./12-controller-catalog.md) | All **371** controllers and their **1,986** public methods |
| [13-model-catalog.md](./13-model-catalog.md) | All **323** Eloquent models, guessed/explicit tables, reference counts |
| [14-document-catalog.md](./14-document-catalog.md) | All **169** markdown files and what each is for |
| [15-redundancy-findings.md](./15-redundancy-findings.md) | Dead views, unused models, duplicated CRUD, god-classes |

Previous ERP strategy audit (`01`–`10`, `MASTER-ERP-AUDIT.md`) is still useful for *business* gaps. Its headline counts (**235 controllers / 242 models**) are stale. Live counts are below.

Regenerate with:

```bash
python docs/system-audit/inventory_audit.py
python docs/system-audit/generate_catalogs.py
```

---

## 1. What this system is

Laravel 12 school ERP (single-tenant, `campus` lower/upper only) with:

- **Web:** Blade + AdminLTE + Vite/Bootstrap
- **Mobile:** Expo/React Native (`mobile-app/`, admin + users/edulynk apps) talking to **109 API controllers**
- **Public website CMS:** 31 Website controllers + 48 Website models living inside the same app
- **Auth:** Sanctum, Spatie permission, Google Socialite, WebAuthn, OTP
- **Integrations:** M-Pesa, Jenga, HostPinnacle SMS, Wasender WhatsApp, S3, BioTime, OpenAI/HF, DomPDF, Excel

There is **no Livewire**. Almost every screen is a classic controller → Blade page. That is why most Blades are “used once”: one controller method returns one page. That is normal MVC, not waste.

---

## 2. Live size

| Layer | Count | Notes |
|---|---:|---|
| Blade views | 861 | 183 finance + 150 academics alone |
| HTTP controllers | 371 | 109 Api (incl. Website API) |
| Public controller methods | 1,986 | Average ~5.3 methods each; outliers to 42 |
| Eloquent models | 323 | 208 still dumped in `app/Models/` root |
| Domain services | 193 | Where fee/timetable/comms logic actually lives |
| Artisan commands | 96 | Many one-off finance *repair* scripts |
| Jobs | 15 | Bulk SMS/WhatsApp/email, PDFs, curriculum parse |
| Mailables | 3 | Thin; most comms go through services/jobs |
| Notifications | 9 | |
| Policies | 8 | Authorization is not model-policy based |
| Form requests | 17 | Almost all validation is inline in controllers |
| Middleware | 10 | |
| Excel exports | 12 | |
| Markdown docs | 169 | Heavy historical overlap |
| Route files | 6 | `web`, `api`, `teacher`, `senior_teacher`, `console`, `channels` |

**Shape of the product (by Blade folder):** finance is the gravity well, then academics, then a long tail of small modules.

---

## 3. Module map (what each area is *for*)

### 3.1 Web Blade modules

| Folder | Views | Purpose |
|---|---:|---|
| `finance/` | 183 | Fees, voteheads, posting, invoices, payments, receipts, M-Pesa/C2B, bank statements, expenses, petty cash, GL/budgets, optional fees, transport fees, discounts, payment plans |
| `academics/` | 150 | Classrooms, streams, subjects, CBC strands, exams, marks, report cards, timetable, lesson plans, schemes, homework, competencies, promotions, curriculum AI |
| `communication/` | 43 | Bulk SMS/email/WhatsApp, templates, announcements, notes, fee-reminder automation |
| `students/` | 43 | Registry, bulk import, records (medical/disciplinary/academic/activities), family integrity, duplicates |
| `website/` | 38 | School public-site CMS (pages, blog, SEO, builder, meals, AI) |
| `dashboard/` | 34 | Role dashboards + KPI widgets (admin/teacher/parent/finance/transport) |
| `hr/` | 34 | Payroll periods, payslips, advances, statutory, imports, analytics |
| `staff/` | 29 | Staff CRUD, leave, documents, attendance, profile, registrations |
| `components/` | 28 | Sprint UI kit (`x-*`). Most of it is unused; screens still use AdminLTE markup |
| `vendor/` | 26 | AdminLTE, Laravel mail, pagination — package views |
| `settings/` | 21 | School settings, terms, IDs, placeholders, access lookups |
| `pos/` | 20 | Shop products, uniforms, discounts, teacher requirements |
| `inventory/` | 19 | Items, requisitions, requirement templates/assignments, student requirements |
| `transport/` | 17 | Transport records, import, assignments (see also vehicles/trips/dropoffpoints) |
| `reports/` | 12 | Class/subject/staff-weekly/operations/student-followup writers |
| `teacher/` | 12 | Teacher portal (leave, salary, fee clearance, students) |
| `partials/` | 11 | Global SMS/email forms, term options |
| `auth/` | 10 | Login, passwords, verify/register leftovers |
| `errors/` | 10 | HTTP error pages (framework-wired) |
| `layouts/` | 10 | App shells + **role navs** (admin/teacher/senior teacher) |
| `attendance/` | 9 | Marking, consecutive absences, reason codes |
| `families/` | 8 | Family hub / linking (overlaps `family/`, `family_update/`) |
| `operations/` | 7 | Assets, visitors, student concerns |
| `senior_teacher/` | 7 | Senior teacher workspace |
| `activities/` | 6 | Extra-curricular + parent change requests |
| `student_assignments/` | 6 | Assign students to classes |
| `swimming/` | 6 | Swimming wallet/payments/attendance |
| `dropoffpoints/` | 5 | Drop-off CRUD + import |
| `attendance_notifications/` | 4 | Absence SMS audience setup |
| `emails/` | 4 | Mailable bodies |
| `events/` | 4 | Calendar CRUD |
| `family_update/` | 4 | Parent self-service family update |
| `trips/` | 4 | Trip CRUD (parallel to transport) |
| Smaller folders (1–3 views) | 33 | `documents`, `driver`, `online_admissions`, `student_categories`, `vehicles`, `activity-logs`, `admin`, `exports`, `family`, `legal`, `parent`, `pdf`, `backup_restore`, `gallery`, `home`, `parents`, `sms_logs`, `system-logs`, `users`, `welcome` |

Every individual file is listed in [11-blade-catalog.md](./11-blade-catalog.md).

### 3.2 Controllers by folder

| Folder | Controllers | Purpose |
|---|---:|---|
| `Api` | 92 + 16 Website API | Sanctum JSON for mobile admin/users apps and public site |
| `Finance` | 51 | Receivables + payments + bank + expenses + accounting |
| `Academics` | 39 | CBC/exams/timetable/lesson plans |
| root (`app/Http/Controllers/*.php`) | 39 | Dashboard, comms, trips, vehicles, drop-offs, logs, webhooks, leftovers |
| `Website` | 31 | Public CMS admin |
| `Hr` | 23 | Payroll and staff records |
| `Students` | 15 | Registry, families, credentials, imports |
| `Inventory` / `Pos` | 9 / 9 | Stock and shop |
| `Reports` | 7 | Written reports |
| `Teacher` / `Swimming` / `Transport` | 6 / 5 / 5 | Role or domain slices |
| `Attendance` / `Library` / `Operations` | 3 / 3 / 3 | Thin but live |
| `Activities` / `Hostel` / `Settings` / `WebAuthn` | 2 each | Hostel is routed but mess/kitchen models are dead |
| Singletons | 1 each | Admin, Auth, Driver, ParentPortal, SeniorTeacher, Users |

Every method is listed in [12-controller-catalog.md](./12-controller-catalog.md).

### 3.3 Models by folder

| Folder | Models | Purpose |
|---|---:|---|
| `app/Models/` (root) | 208 | Historical dump: students, finance, HR, transport, inventory, comms, swimming… |
| `Academics/` | 49 | Exams, timetable, CBC, diaries, schemes |
| `Website/` | 48 | CMS, SEO, community, media |
| `Pos/` | 6 | Shop |
| `AcademicReports/` / `Reports/` | 5 / 5 | Report templates / written reports |
| `Admissions/` | 2 | Online admissions |

Every model is listed in [13-model-catalog.md](./13-model-catalog.md).

---

## 4. One-time-use vs actually redundant

### 4.1 One Blade per controller method is not waste

The scanner found **651 Blades referenced from a single file**. That is expected: `PaymentController@create` → `finance.payments.create`. Deleting those would delete the product.

**Treat as redundant only if:**

1. **Zero references** (dead view), or
2. **Create + edit copy the same form** (repetition), or
3. **Two modules do the same job** (structural overlap), or
4. **Controller is never routed** (dead stack).

### 4.2 Likely-dead Blade pages (no static `view()` / `@include`)

Highest-confidence orphans (full table in [15](./15-redundancy-findings.md)):

**Superseded academics leftovers**

- `academics.class`, `academics.sections`, `academics.assign_class_teacher`
- `academics.class_timetable`, `academics.teacher_timetable` (replaced by `academics.timetable.*`)
- `academics.promote_students` (replaced by `academics.promotions.*`)
- `academics.curriculum_assistant.index` — Blade exists, but routes only expose JSON `generate` / `chat` (no GET page)
- `academics.report_cards.edit`, `academics.exam_results.publish_summary`

**Dashboard widgets not included anywhere**

- `dashboard.partials.{absence_table, activity, enrolment_chart, exam_performance, overview, recent_admissions, students, summary, system_health, transport_widget}`

**Finance leftovers**

- `finance.mpesa.c2b-allocate` (**984 lines**, no references at all)
- `finance.discounts.allocate` (225 lines)
- `finance.credit_debit_adjustments.create` / `.show` (show is 2 lines)
- `finance.transport_fees.partials.import_tabs`

**Transport split leftovers**

- `transport.create` / `edit` / `show` (CRUD pages unused; other transport screens remain)
- `transport.student_dropoffs.index` (247 lines; drop-offs also live under `dropoffpoints/` and `StudentDropOffController`)

**Other**

- `communication.templates.send_sms`
- `student_assignments.{create, edit, bulk_assign}`
- `students.bulk-parse`
- `settings.access_lookups`, `settings.term_days.index`, `settings.partials.features`
- `exports.directory_table`, `exports.enrollment_by_class`
- `staff.partials.staff_bank_statutory`

**Do not delete**

- `layouts.partials.nav-*` — loaded **dynamically** by `App\Support\NavAccess`
- `errors.*` — Laravel error renderer
- PDF/print/public views with no static string — often `Pdf::loadView($name)`

**Broken file:** `resources/views/finance/invoices/adjust,blade.php` (comma in the filename). Unreachable.

### 4.3 Unused design-system components (16)

`resources/views/components/{form.*, data.*, feedback.*, nav.tabs, section, divider, icon-button, student-search}` have **no `<x-…>` hits**. The UI kit was added; almost no Blade screen adopted it. `components.student-search` is 210 lines of unused widget.

### 4.4 Dead / unfinished models (14 with zero refs outside themselves)

| Cluster | Models | Verdict |
|---|---|---|
| Boarding/mess abandoned | `HostelAttendance`, `KitchenRecipient`, `MessMenu`, `MessSubscription` | Hostel *allocations* are routed; mess/kitchen never wired |
| Staff 360 schema, no web | `PerformanceGoal`, `PerformanceFeedback`, `StaffSkill`, `StaffCertification`, `StaffQualification`, `TrainingCourse`, `TrainingRequest` | API has thin `ApiStaffPerformance` / `ApiStaffTraining`; no Blade module |
| Twin settings | `SystemSetting` | Live settings use `Setting` |
| Exam leftover | `ExamItem` | Exam model moved on |
| Website leftover | `SeoMeta` | Other SEO models are used |

**66 more models** are used in only 1–2 files (feature stubs). Listed in [15 §9](./15-redundancy-findings.md).

### 4.5 Unrouted / leftover controllers

| Controller | Verdict |
|---|---|
| `HomeController` | Laravel UI leftover. Not in any route file. `DashboardController` owns home. |
| `Finance\FeeStatementController` | `use`d in `web.php` but never routed. Replaced by `StudentStatementController`. |
| `Finance\ReceiptController` | Same: `use`d, never routed. Receipts live on `PaymentController`. |
| `Controller` | Base class — ignore |
| `ResolvesDocumentStorage` | Concern, not a controller — ignore |
| `WebAuthn*` | Likely registered by Laragear package, not `routes/*.php` |
| `ExamGroupController` / `ExamPaperController` | Confirm nested exam routes; may be called from exam UI only |

---

## 5. Repetition (the expensive kind)

### 5.1 Create + edit copied 46 times, shared form only 6 times

**Good (shared `_form` / `form` partial):**  
`finance.voteheads`, `finance.payment_thresholds`, `pos.products`, `website.{blogs, events, pages}`

**Copied fields (46 pairs)** include almost all of academics CRUD, staff, students + student records, HR payroll lookups, inventory templates, vehicles, trips, drop-off points, events, expenses, payment methods, bank accounts. Full list: [15 §5](./15-redundancy-findings.md).

This is the single highest-ROI Blade cleanup: extract `_form.blade.php` per resource.

### 5.2 Same domain, several Blade trees

| Domain | Folders / controllers | Problem |
|---|---|---|
| People | `staff/` + `hr/` + `Hr\StaffController` | Two IA trees (profile/leave vs payroll) |
| Transport | `transport/` + `vehicles/` + `trips/` + `dropoffpoints/` + root `TripController` / `VehicleController` / `StudentDropOffController` | Four CRUD apps for one bounded context |
| Family | `families/` + `family/` + `family_update/` + `parent/` + `parents/` | Five trees for family hub + parent diary + self-update |
| Logs | `activity-logs/` + `system-logs/` + `sms_logs/` | Three log UIs |
| Extra-curricular | `extra-curricular-activities` **and** aliased `activities.*` routes | Same controller bound twice in `web.php` |
| Statements | `FeeStatementController` leftover vs `StudentStatementController` | Dead twin |

### 5.3 Web controller + API controller twins (expected, but large)

33 domains have both `FooController` and `ApiFooController` (classroom, homework, timetable, attendance, communication, invoices, …). That duplication is the mobile split, not a bug — but **logic is copied in controllers** instead of always living in a service. Finance is better (services exist); several academic/API pairs re-query Eloquent directly.

### 5.4 God-class controllers (one-time-use *methods* that should be services)

| Controller | Public methods | Lines |
|---|---:|---:|
| `Finance\BankStatementController` | 42 | 6,766 |
| `Finance\PaymentController` | 33 | 3,740 |
| `Finance\MpesaPaymentController` | 31 | 2,955 |
| `Students\StudentController` | 30 | 2,894 |
| `CommunicationController` | 28 | 2,153 |
| `Academics\TimetableController` | 22 | 1,074 |
| `Finance\InvoiceController` | 20 | 1,024 |
| `Hr\StaffController` | 17 | 1,319 |
| `Attendance\AttendanceController` | 14 | 1,017 |
| `Finance\TransportFeeController` | 14 | 966 |

These are not unused — they are **too used**. Many methods are one-off repair/import/preview actions that belong in commands or services (`c2b-allocate` already escaped into a 984-line orphan view).

### 5.5 Policies and FormRequests are thin vs surface area

8 policies and 17 form requests for 371 controllers. Validation and authorization are copy-pasted inside methods. That is repetitive even when the Blade is unique.

### 5.6 Artisan commands (96)

A large fraction are **one-time data repairs** (`FixElianaPaymentAllocationOrder`, `FixEquityRefsByMatch`, `FinanceCarryForwardScrapAll`, …). They are useful as runbooks but they clutter `app/Console/Commands`. Candidates to archive under `app/Console/Commands/OneOff/` or `docs/runbooks/`.

---

## 6. Documents (169 markdown files)

Every file: [14-document-catalog.md](./14-document-catalog.md).

| Cluster | Count | Purpose | Keep? |
|---|---:|---|---|
| `docs/` root | 49 | Deployment, testing, finance/UI summaries, overlapping “complete” write-ups | Keep operational runbooks; archive duplicate summaries |
| `docs/execution/` | 30 | Sprint completion reports | Historical — do not treat as current spec |
| `docs/design-system-v3/` | 14 | Tokens, typography, icons, nav | Current UI spec for web |
| `docs/system-audit/` | this folder | ERP strategy (`01–10`) + **this live inventory (`00`, `11–15`)** | `01–10` counts are stale; use `00`/`11–15` for files |
| `docs/app-split/` | 8 | Mobile two-app architecture | Current for mobile |
| `docs/admin-app/` | 7 | Admin app IA / Play Store | Current for admin app |
| `docs/ui/` | 8 | Sprint UX reports | Historical |
| `docs/play-store/` | 6 | Impersonation appeal pack | Legal/ops |
| `mobile-app/` | 11 | Mobile README, in-app updates, user-app notes | Current |
| Other | rest | PRD, admissions, finance workspace, people/staff360, website AGENTS | Mixed |

**Document redundancy:** `docs/SYSTEM_DOCUMENTATION.md` (Jan 2026), `docs/IMPLEMENTATION_COMPLETE_SUMMARY.md`, `docs/mobile-app-FINAL_SUMMARY.md`, `docs/USERS_APP_*`, and the old `MASTER-ERP-AUDIT` all describe the same system at different dates. This live inventory is the file-level source of truth as of 22 Sep 2026.

Root `README.md` is still the **Laravel skeleton README** — it does not describe this school ERP.

---

## 7. Cleanup priority (if you want a follow-up implementation)

1. **Safe deletes / archive:** `HomeController` + `home.blade.php`; unused `use` of `FeeStatementController` / `ReceiptController` if nothing else references them; typo `adjust,blade.php`; `finance.mpesa.c2b-allocate`; dashboard partials not included; academics leftover pages listed in §4.2.
2. **Confirm then delete:** PDF views with no static ref; `student_assignments.create/edit`; `transport.create/edit/show`.
3. **DRY Blades:** shared `_form` for the 46 create/edit pairs.
4. **Merge IA:** transport + vehicles + trips + drop-offs; staff + hr navigation; family* folders.
5. **Split god-classes:** `BankStatementController`, `PaymentController`, `MpesaPaymentController` first.
6. **Either adopt or delete** the unused `resources/views/components` UI kit.
7. **Archive** one-off Artisan repair commands.
8. **Adopt or delete** unused boarding/mess/performance models (schema may still have tables).

Do not bulk-delete the 651 “one-use” pages. That would remove the application.
