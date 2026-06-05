# 01 — Academics Workspace Audit (Laravel ERP → Admin App)

**Status:** Complete (read-only discovery)  
**Sprint:** Academics Workspace Discovery  
**Scope:** Academic oversight workspace — exams, marks, assessments, report cards, CBC configuration visibility, curriculum library, moderation queues. **Not** mark entry (Staff App), **not** full Assessment Engine transformation (R4), **not** timetable generation, **not** KNEC export.  
**No application code** was written for this exercise.

**Primary sources:** [`docs/system-audit/06-academic-audit.md`](../system-audit/06-academic-audit.md), [`docs/system-audit/05-business-processes.md`](../system-audit/05-business-processes.md) (§5–6), [`docs/academic-transformation/assessment-engine-design.md`](../academic-transformation/assessment-engine-design.md), [`docs/execution/student360-academic-audit.md`](../execution/student360-academic-audit.md), [`docs/prd/02-MASTER-PRODUCT-BACKLOG.md`](../prd/02-MASTER-PRODUCT-BACKLOG.md) (R4/E10–E13), [`docs/admin-app/02-admin-information-architecture.md`](../admin-app/02-admin-information-architecture.md) (§1 Academics, §J4), [`docs/admin-app/03-admin-ui-specifications.md`](../admin-app/03-admin-ui-specifications.md) (CBC Hub), `routes/api.php`, `routes/web.php`, API controllers under `app/Http/Controllers/Api/`, web academics controllers, `AssessmentReadFacade`, legacy `mobile-app/src/api/academics.api.ts`, `apps/admin` Academics placeholder.

---

## Executive summary

The ERP is a **production-grade traditional exam-based academic system** with **CBC-aware schema** (strands, rubrics, portfolios, curriculum PDFs) but **exam marks remain the primary grading instrument** (see academic audit). For the Admin App Academics Workspace, the backend exposes **strong read APIs for exams, class-scoped marks, exam analytics, and student-scoped report cards/assessment history**; **school-wide oversight surfaces** (report-card registry, marks submission progress, CBC coverage, curriculum tree, exam moderation actions) are largely **web-only**.

| Workspace area | Backend readiness | Admin App today |
|----------------|-------------------|-----------------|
| **Dashboard** | Partial — no `/academics/summary`; composable from `/exams`, `/reports/exams/*`, `/lesson-plans/review-queue` | Placeholder tab (`AcademicsScreen`) |
| **Assessments** | Partial — **student-scoped** `GET /students/{id}/assessment-history` (Phase 0 facade); no school-wide assessment registry | Student 360 Academics tab only (`@erp/core`) |
| **Exams** | Strong read — `GET /exams`, `GET /exams/{id}`; setup/publish **web-only** | Legacy monolith staff screens; `@erp/admin` empty |
| **Marks** | Strong read (class/exam scoped) — `GET /marks`, `GET /marks/matrix`; entry **API write** (Staff) | Not started |
| **Report Cards** | Partial — `GET /report-cards?student_id=` + `GET /report-cards/{id}`; batch generate/publish **web-only** | Student 360 only |
| **CBC Hub** | Weak mobile — strands/portfolios/coverage **web-only**; CBC on marks/report cards in DB not fully exposed | Not started |
| **Curriculum** | Web-only — `curriculum_designs`, parse/RAG assistant | Not started |
| **Moderation** | Partial — lesson-plan review **API**; exam status transitions + report-card publish **web-only** | Not started |

**Recommendation:** Ship Academics Workspace **read-only MVP** by porting existing Sanctum APIs into `@erp/core` + `@erp/admin` (mirror Finance/Admissions pattern). Phase 2 adds **thin REST wrappers** for school-wide registries and moderation actions already implemented on web controllers. Defer Assessment Engine (R4), official CBC report layout, and KNEC module.

**Admin App principle (IA):** *Configure, approve, oversee, report* — capture (mark entry, formative rubric grids) stays in **Staff App**.

---

# 1. Academic API inventory

All routes below are under `auth:sanctum` unless noted. Response envelope: `{ success, data, message? }`; paginated lists use `data.data`, `current_page`, `last_page`, `total`.

## 1.1 Exams & marks

| Endpoint | Controller | Methods | Mobile-ready | Notes |
|----------|------------|---------|--------------|-------|
| `/exams` | `ApiAcademicsController::exams` | GET | ✅ Yes | Filters: `status`, `per_page`. Teachers scoped to assigned classrooms / `exam_class_subject` pivot. **Gap:** no `classroom_id`, `term_id`, `academic_year_id` server filters — client-side filter. |
| `/exams/{id}` | `ApiAcademicsController::showExam` | GET | ✅ Yes | Detail with classroom, subject, term, academic year. Teacher scope enforced. |
| `/exams/{id}/marking-options` | `ApiAcademicsController::examMarkingOptions` | GET | ✅ Yes | Class/subject combos for marking UI (Staff). |
| `/marks` | `ApiAcademicsController::marks` | GET | ✅ Yes | **Required:** `exam_id`, `subject_id`, `classroom_id`. Returns **whole class** marks. Teacher classroom scope. |
| `/exam-marks/batch` | `ApiAcademicsController::batchMarks` | POST | ✅ Yes (Staff) | Mark entry — **out of Admin MVP** (oversight read-only). |
| `/marks/matrix/context` | `ApiAcademicsController::marksMatrixContext` | GET | ✅ Yes | Classrooms, exam types, streams for matrix. |
| `/marks/matrix` | `ApiAcademicsController::marksMatrix` | GET | ✅ Yes | Students × exams grid for one class/type. |
| `/exam-marks/matrix/batch` | `ApiAcademicsController::batchMarksMatrix` | POST | ✅ Yes (Staff) | Matrix write — **out of Admin MVP**. |

**Tables:** `exams`, `exam_types`, `exam_sessions`, `exam_class_subject`, `exam_schedules`, `exam_marks`, `grading_schemes`, `grading_bands`, `cbc_performance_levels`.

**Services:** `ClassroomGradingService`, `ClassSheetBuilder`, exam status transitions on `Exam` model.

**Permissions:** `exams.view`, `exams.enter_marks`, `exams.publish`, `exams.approve`, `exam_marks.view`, `exam_marks.create` (Senior Teacher seeder).

## 1.2 Exam reports & analytics

| Endpoint | Controller | Methods | Mobile-ready | Notes |
|----------|------------|---------|--------------|-------|
| `/reports/exams/class-sheet` | `ApiExamReportsController::classSheet` | GET | ✅ Yes | `mode=exam\|term`. Term: `academic_year_id`, `term_id`, `classroom_id`, optional `stream_id`. Cached via `ReportCache`. |
| `/reports/exams/teacher-performance` | `ApiExamReportsController::teacherPerformance` | GET | ✅ Yes | `scope=class\|school` (school requires full access). |
| `/reports/exams/subject-performance` | `ApiExamReportsController::subjectPerformance` | GET | ✅ Yes | Class/exam scoped. |
| `/reports/exams/student-insights` | `ApiExamReportsController::studentInsights` | GET | ✅ Yes | Top 10 / most improved (class lists). |
| `/reports/exams/trends` | `ApiExamReportsController::trends` | GET | ✅ Yes | Class/stream mean series per exam. |
| `/reports/exams/insights` | `ApiExamReportsController::insights` | GET | ✅ Yes | Text insights from trends. |
| `/reports/exams/mastery-profile` | `ApiExamReportsController::masteryProfile` | GET | ✅ Yes | Class mastery bands. |
| `/reports/exams/export/class-sheet.xlsx` | `ApiExamReportsController::exportClassSheet` | GET | ✅ Yes | Excel export (open via link). |
| `/reports/exams/export/term-workbook.xlsx` | `ApiExamReportsController::exportTermWorkbook` | GET | ✅ Yes | Term workbook export. |

**Access:** `ExamReportsAccess::assertClassroomAccess` — Admin/Secretary/Super Admin full; teachers assigned/supervised classes.

**Tables:** reads `exam_marks`, `exams`, `students`, `classrooms`, `streams`, `subjects`.

## 1.3 Student-scoped academics (Phase 0 read facade)

| Endpoint | Controller | Methods | Mobile-ready | Notes |
|----------|------------|---------|--------------|-------|
| `/students/{student}/assessment-history` | `ApiStudentAssessmentController::assessmentHistory` | GET | ✅ Yes | Unified history from `AssessmentReadFacade`: exam marks, weekly `assessments`, portfolios, report cards. Filters: `type`, `term_id`, `academic_year_id`, `subject_id`, date range. **Student-scoped only.** |
| `/students/{student}/academic-summary` | `ApiStudentAssessmentController::academicSummary` | GET | ✅ Yes | KPIs: `exam_average`, `latest_overall_percentage`, performance level, counts by type. |
| `/report-cards` | `ApiReportCardController::index` | GET | ✅ Yes | **Requires `student_id`.** Index zeros `overall_percentage` — use show for metrics. |
| `/report-cards/{id}` | `ApiReportCardController::show` | GET | ✅ Yes | Full card via `ReportCardBatchService::build`. **CBC block computed but not fully in mobile JSON.** |
| `/students/{id}/stats` | `ApiStudentController::stats` | GET | ✅ Supporting | `exam_average` (often null). |

**Services:** `AssessmentReadFacade`, `AssessmentTypeResolver`, `ReportCardBatchService`, `ReportCardAccessService`.

**Tables:** `exam_marks`, `assessments`, `portfolio_assessments`, `report_cards`, `cbc_performance_levels`.

**Already in `@erp/core`:** `academicsApi`, `useStudentAcademicSummary`, `useStudentReportCards`, `useStudentAssessmentHistory` (Student 360).

## 1.4 Lesson plans & schemes (moderation-adjacent)

| Endpoint | Controller | Methods | Mobile-ready | Notes |
|----------|------------|---------|--------------|-------|
| `/lesson-plans` | `ApiLessonPlansController::index` | GET | ✅ Yes | Filters: `classroom_id`, `subject_id`, `status`, `submission_status`, `academic_year_id`, `term_id`. |
| `/lesson-plans/review-queue` | `ApiLessonPlansController::reviewQueue` | GET | ✅ Yes | Submitted plans awaiting review (Senior Teacher / Admin). |
| `/lesson-plans/{id}` | `ApiLessonPlansController::show` | GET | ✅ Yes | Detail with CBC fields (`substrand_id`, `core_competencies`). |
| `/lesson-plans/{id}/approve` | `ApiLessonPlansController::approve` | POST | ✅ Yes | Moderation action. |
| `/lesson-plans/{id}/reject` | `ApiLessonPlansController::reject` | POST | ✅ Yes | Moderation action. |
| `/lesson-plans` | `ApiLessonPlansController::store` | POST | ⚠️ Staff | Create — Staff App primary. |
| `/lesson-plans/{id}/submit` | `ApiLessonPlansController::submit` | POST | ⚠️ Staff | Submit for review. |

**Tables:** `lesson_plans`, `schemes_of_work` (schemes **no API** — web only).

**Permissions:** `lesson_plans.view`, `schemes_of_work.approve` (web).

## 1.5 Homework & academic reports (adjacent, not core workspace)

| Endpoint | Controller | Purpose | Workspace fit |
|----------|------------|---------|---------------|
| `/assignments` | `ApiHomeworkController` | Homework list/create | **Exclude** from Academics MVP (no per-student scores in API). |
| `/academic-reports/*` | `ApiAcademicReportsController` | Staff qualitative form templates/submissions | **Exclude** — not learner assessment. |

## 1.6 Structure & settings (supporting)

| Endpoint | Controller | Methods | Notes |
|----------|------------|---------|-------|
| `/classes` | `ApiClassroomController::index` | GET | Class picker. |
| `/classes/{id}/streams` | `ApiClassroomController::streams` | GET | Stream picker. |
| `/classes/{id}/subjects` | `ApiClassroomController::subjects` | GET | Subject list per class. |
| `/settings/academic-years` | `ApiSettingsHubController::academicYears` | GET | Year filter. |
| `/settings/terms` | `ApiSettingsHubController::terms` | GET | Term filter. |
| `/settings/subjects` | `ApiSettingsHubController::subjects` | GET | Subject catalog. |
| `/settings/grading` | `ApiSettingsHubController::gradingSchemes` | GET | Grading schemes read. |
| `/timetables/student/{id}` | `ApiTimetableController::student` | GET | Student timetable (Student 360). |
| `/timetables/teacher/{staffId}` | `ApiTimetableController::teacher` | GET | Teacher timetable. |

**Tables:** `classrooms`, `streams`, `subjects`, `learning_areas`, `classroom_subjects`, `academic_years`, `terms`, `grading_schemes`, `grading_bands`, `timetable_*`.

## 1.7 Senior teacher supervision

| Endpoint | Controller | Methods | Notes |
|----------|------------|---------|-------|
| `/senior-teacher/supervised-classrooms` | `ApiSeniorTeacherController` | GET | Supervised class list. |
| `/senior-teacher/supervised-staff` | `ApiSeniorTeacherController` | GET | Teachers under supervision. |
| `/senior-teacher/students` | `ApiSeniorTeacherController` | GET | Students in supervised classes. |

**Role gate:** Senior Teacher / Supervisor only. Supports scoped oversight in Exams/Marks/Reports.

## 1.8 Dashboard (cross-module)

| Endpoint | Controller | Academics fields | Notes |
|----------|------------|------------------|-------|
| `/dashboard/stats` | `ApiDashboardController::stats` | Teacher: `pending_marks`. Admin: enrollment/fees charts — **no academics KPIs**. | Compose dashboard client-side for MVP. |

## 1.9 Web-only academics APIs (not in `routes/api.php`)

| Module | Web routes | Primary actions |
|--------|------------|-----------------|
| Exam CRUD & bulk | `exams.*`, `exams/bulk-*` | Create, schedule, status transitions (`draft`→`moderation`→`approved`→`published`) |
| Exam publish to RC | `POST exams/publish/{exam}` | `ExamPublishingController` — pushes marks into report cards |
| Report cards | `report_cards.*` | Batch generate, publish, PDF, public token |
| CBC strands/substrands | `cbc-strands.*`, `cbc-substrands.*` | CRUD tree |
| Portfolio | `portfolio-assessments.*` | CRUD + evidence |
| Curriculum designs | `curriculum-designs.*` | PDF upload, parse, review |
| Curriculum AI | `curriculum-assistant.*` | RAG generate/chat |
| Schemes of work | `schemes-of-work.*` | Generate, approve, export |
| Weekly assessments | `assessments.*` (`AssessmentController`) | Numeric weekly scores — **no REST API** |
| Exam analytics (web) | `exam-analytics.*`, `exam-reports.*` | Mirrors API reports + PDF exports |
| Heatmaps | `HeatmapController` | Subject `assessments.score_percent` — web session |

---

# 2. Exams audit

## 2.1 Domain model

- **Primary entity:** `Exam` — linked to `exam_type_id`, `academic_year_id`, `term_id`, optional `classroom_id`/`stream_id`/`subject_id`, multi-class via `exam_class_subject` pivot.
- **Lifecycle:** `draft` → `open` → `marking` → `moderation` → `approved` → `published` → `locked` (see `Exam::canTransitionTo`).
- **Flags:** `publish_exam`, `publish_result`, `is_cat`, `cat_number`, `sba_weight`, `exam_category` (formative/summative).
- **Sessions/schedules:** `exam_sessions`, `exam_schedules` (web management).

## 2.2 API suitability

| Use case | API | Gap |
|----------|-----|-----|
| Exam registry (Admin) | `GET /exams` | Missing server filters for class/term/year; no `marks_submitted_pct` |
| Exam detail | `GET /exams/{id}` | ✅ Ready |
| Exam analytics | `/reports/exams/*` | Class/term scoped — good for drill-down |
| Create/edit exam | Web `ExamController` | ❌ No mobile API |
| Status transition (moderation) | Web `ExamController` | ❌ No `PUT /exams/{id}/status` |
| Publish results to report cards | Web `ExamPublishingController` | ❌ No API |

## 2.3 Permissions

| Permission | Action |
|------------|--------|
| `exams.view` | List/detail read |
| `exams.create` / `exams.edit` / `exams.delete` | Setup (web today) |
| `exams.enter_marks` | Mark entry (Staff) |
| `exams.approve` | Approve after moderation |
| `exams.publish` | Publish to report cards |
| `exams.calculate_grades` | Recalculate grades |

## 2.4 Admin workspace mapping

| Screen | Immediate (no backend) | Backend required |
|--------|------------------------|------------------|
| Exams list | `GET /exams` + client filters | Enriched index: class/term filters, submission % |
| Exam detail | `GET /exams/{id}` | Schedule pivot in API response |
| Exam analytics | `/reports/exams/trends`, `subject-performance` | School-wide exam summary endpoint |

---

# 3. Marks audit

## 3.1 Domain model

- **Primary entity:** `ExamMark` — `student_id`, `exam_id`, `subject_id`, `score_raw`, `score_moderated`, component scores (`opener_score`, `midterm_score`, `endterm_score`), `grade_label`, `performance_level_id`, `competency_scores` (JSON), `assessment_method`, `rubrics`, `teacher_id`.
- **Effective score:** `score_moderated ?? score_raw` (used in `ClassSheetBuilder`, report cards).
- **No student-scoped marks list API** — only class+exam+subject bundle.

## 3.2 API suitability

| Use case | API | Notes |
|----------|-----|-------|
| View class marks for one exam/subject | `GET /marks` | Admin oversight — read-only |
| Matrix overview (class × exams) | `GET /marks/matrix` | Submission progress proxy |
| Student marks history | `GET /students/{id}/assessment-history?type=traditional_exam,cat` | ✅ Via facade (Student 360 / drill-down) |
| Moderate marks (`score_moderated`) | Web `ExamMarkController` | ❌ No API |
| Enter marks | `POST /exam-marks/batch` | Staff App — exclude Admin MVP |

## 3.3 Permissions

`exams.enter_marks`, `exams.import_marks`, `exam_marks.view`, `exam_marks.create` (Senior Teacher).

## 3.4 Admin workspace mapping

| Screen | Immediate | Backend required |
|--------|-----------|------------------|
| Marks oversight (class sheet) | `GET /marks` + class/exam/subject pickers | `GET /academics/marks/progress?term_id&classroom_id` |
| Matrix progress view | `GET /marks/matrix` | Aggregated % submitted per class |
| Mark detail row | Filter `/marks` response | Single mark `GET /exam-marks/{id}` (optional) |

---

# 4. Assessments audit

## 4.1 Naming vs reality

The ERP has **two parallel systems** (see §06 academic audit):

| System | Table | API today | Role |
|--------|-------|-----------|------|
| **Exams / exam marks** | `exams`, `exam_marks` | `/exams`, `/marks`, exam reports | Primary — report cards, rankings |
| **Weekly assessments** | `assessments` | **None** (web `AssessmentController` only) | Secondary — subject heatmaps |
| **Portfolios** | `portfolio_assessments` | **None** (web only) | CBC evidence — optional |
| **Homework** | `homework` | `/assignments` | Not scored per student in API |

## 4.2 Phase 0 unified read facade

`AssessmentReadFacade` + `AssessmentTypeResolver` power:

- `GET /students/{student}/assessment-history` — merges exam marks, weekly assessments, portfolios, report-card term entries.
- Types: `traditional_exam`, `cat`, `weekly_assessment`, `portfolio`, `report_card_term`, etc.

**This is student-scoped, not a school-wide Assessments registry.**

## 4.3 Assessment Engine (future — R4/E10)

[`assessment-engine-design.md`](../academic-transformation/assessment-engine-design.md) defines `assessment_events`, `assessment_results`, strangler migration from legacy. **Not implemented** — out of this workspace MVP.

## 4.4 Admin workspace mapping

| Screen | Immediate | Backend required |
|--------|-----------|------------------|
| Assessments (student drill-down) | Reuse Student 360 hooks + student search | — |
| Assessments (school registry) | ❌ | `GET /assessments` or facade index with class/term filters |
| Assessment detail | `assessment-history` row + legacy source | `GET /assessments/{id}` unified detail |

---

# 5. CBC audit

## 5.1 Schema (exists)

| Entity | Table | Linked to |
|--------|-------|-----------|
| Learning areas | `learning_areas` | `subjects.learning_area` |
| Strands | `cbc_strands` | `learning_area_id` |
| Sub-strands | `cbc_substrands` | `strand_id` |
| Core competencies | `cbc_core_competencies` | lesson plans, marks JSON |
| Performance levels | `cbc_performance_levels` | codes `E/M/A/B` from **exam %** (`CBCPerformanceLevel::getByScore`) |
| Rubrics | `assessment_rubrics` | `cbc_substrand_id` — populated on curriculum parse, **not main marking UI** |
| Portfolio | `portfolio_assessments` | evidence + rubric JSON |

## 5.2 API gaps (critical)

| Expected (UI spec) | Status |
|--------------------|--------|
| `GET /cbc/curriculum?grade&learning_area` | ❌ Web `cbc-strands` only |
| `GET /cbc/coverage?class&term` | ❌ No coverage service/API |
| `GET /cbc/portfolios?class` | ❌ Web `portfolio-assessments` only |
| `GET /students/{id}/competencies` | ❌ Not implemented |
| CBC on report-card show | ⚠️ Computed in DTO, **stripped from mobile JSON** |
| CBC on exam marks API | ❌ `performance_level_id`, `competency_scores` not in `/marks` response |

## 5.3 Compliance note

System is **exam-driven with CBC enrichments**, not competency-first (see 06-academic-audit gap table). CBC Hub in Admin App should be **configuration & oversight visibility**, not claiming full KICD formative grids until R4.

## 5.4 Admin workspace mapping

| Screen | Immediate | Backend required |
|--------|-----------|------------------|
| CBC Hub — Performance levels | `GET /settings/grading` (partial) | `GET /cbc/performance-levels` |
| CBC Hub — Curriculum tree | ❌ | `GET /cbc/curriculum` (wrap strands/substrands) |
| CBC Hub — Coverage | ❌ | `GET /cbc/coverage` (scheme/lesson vs substrands) |
| CBC Hub — Portfolios | Student history includes portfolio rows | `GET /cbc/portfolios?classroom_id` |
| Student CBC (360 tab) | Defer or minimal via report-card show | Expose `cbc` block on `GET /report-cards/{id}` |

---

# 6. Report card audit

## 6.1 Domain model

- **Entity:** `report_cards` — per student/term/classroom; `published_at`, `public_token`; skills (`report_card_skills`), behaviours, remarks; CBC JSON columns (`performance_summary`, `core_competencies`, `learning_areas_performance`, `cat_breakdown`, `portfolio_summary`).
- **Generation:** `ReportCardBatchService::generateForClass` (web) — aggregates `exam_marks`, attendance, skills.
- **Access:** `ReportCardAccessService` — parent fee-gating on published view.

## 6.2 API suitability

| Use case | API | Gap |
|----------|-----|-----|
| Student report card list | `GET /report-cards?student_id=` | Index lacks real %/grade |
| Report card detail | `GET /report-cards/{id}` | ✅ Strong; CBC partial |
| **School-wide registry** (class/term) | ❌ | Required for Admin workspace |
| Batch generate | Web `POST report_cards/generate` | ❌ |
| Publish | Web `POST report_cards/{id}/publish` | ❌ |
| PDF | Web `report_cards/{id}/pdf` | ❌ API (open web URL) |

## 6.3 Permissions

`report_cards.view`, `report_cards.generate`, `report_cards.publish`, `report_cards.remarks.edit`, `report_cards.competencies.edit`, `report_cards.skills.edit`.

## 6.4 Admin workspace mapping

| Screen | Immediate | Backend required |
|--------|-----------|------------------|
| Report Cards (student path) | Student search → existing hooks | — |
| Report Cards (class/term registry) | ❌ | `GET /report-cards?classroom_id&term_id` |
| Report card detail | `GET /report-cards/{id}` | PDF link field in show payload |
| Publish workflow | ❌ | `POST /report-cards/{id}/publish` |

---

# 7. Curriculum audit

## 7.1 Domain model

- **Entities:** `curriculum_designs`, `curriculum_pages`, `curriculum_embeddings` (RAG).
- **Processing:** `CurriculumParsingService` (regex + Smalot PDF) → strands/substrands/rubrics; status `processing` → `processed` / `failed`.
- **AI:** `CurriculumAssistantController` — generate/chat (web, `curriculum_assistant.use`).

## 7.2 API status

**No Sanctum REST** for curriculum designs. All CRUD under web `curriculum-designs.*`.

Related: `SchemeOfWorkAutoGenerationService` uses learning areas/strands — web `schemes-of-work.*` only.

## 7.3 Admin workspace mapping

| Screen | Immediate | Backend required |
|--------|-----------|------------------|
| Curriculum library list | ❌ | `GET /curriculum-designs` |
| Upload / parse status | ❌ | `POST /curriculum-designs` + `GET /{id}/progress` |
| Strand tree viewer | ❌ | `GET /cbc/curriculum` or embed design review payload |
| AI assistant | ❌ | `POST /curriculum-assistant/chat` (governed) |

**MVP recommendation:** Curriculum section is **placeholder + link to web** until read API exists, or **defer** to post-MVP.

---

# 8. Teacher workflow audit

**Primary app:** Staff App (capture). Admin App = oversight.

| Step | Actor | System today | API |
|------|-------|--------------|-----|
| Plan lesson | Teacher | `lesson_plans` draft | `POST /lesson-plans`, `PUT`, `POST submit` |
| Submit for review | Teacher | `submission_status=submitted` | `POST /lesson-plans/{id}/submit` |
| Enter marks | Teacher | `exam_marks` via batch/matrix | `POST /exam-marks/batch` |
| Enter weekly assessment | Teacher | Web `AssessmentController` | ❌ |
| Portfolio evidence | Teacher | Web `portfolio-assessments` | ❌ |
| View class results | Teacher | Exam reports | `GET /reports/exams/class-sheet` |
| View student report card | Teacher | Scoped student access | `GET /report-cards?student_id=` |

**Admin visibility:** Teacher-scoped data appears when Admin user has `exams.view` + classroom access rules mirror server.

---

# 9. Senior teacher workflow audit

| Step | Actor | System today | API |
|------|-------|--------------|-----|
| Supervise classes | Senior Teacher | `getSupervisedClassroomIds()` | `GET /senior-teacher/supervised-classrooms` |
| Review lesson plans | Senior Teacher | Review queue | `GET /lesson-plans/review-queue`, `approve`/`reject` |
| Oversee marks | Senior Teacher | Class sheets for supervised classes | `/marks`, `/reports/exams/*` |
| Exam moderation (approve) | Senior Teacher | Web exam status → `approved` | ❌ API |
| Report card remarks | Senior Teacher | Web skills/remarks | ❌ API |

**Permissions (seeder):** `academics.view`, `exams.view`, `exam_marks.view`, `exam_marks.create`, `report_cards.view`, `report_cards.remarks.edit`, `report_card_skills.edit`.

**Admin Moderation screen (MVP):** Can ship **lesson-plan queue** immediately; exam moderation requires backend.

---

# 10. Principal workflow audit

Principals map to **Admin / Super Admin / Head Teacher** roles with broad permissions.

| Step | Actor | System today | API |
|------|-------|--------------|-----|
| School-wide performance | Principal | Web exam analytics | `/reports/exams/trends` (`scope=school` where allowed) |
| Approve term results | Principal | Exam `approved` → publish → RC batch | Web only |
| Publish report cards | Principal | `report_cards.publish` | Web only |
| Curriculum governance | Principal | Curriculum design review | Web only |
| Dashboard oversight | Principal | Global dashboard | `/dashboard/stats` (no academics tiles) |

**Journey J4 (IA):** Academics → verify marks → moderation → Report Cards → batch generate → publish. **Linear, gated** — Admin workspace should mirror this; today only lesson-plan gate is API-complete.

---

# 11. Academic dashboard KPIs

## 11.1 Proposed KPIs (Admin Academics Dashboard)

| KPI | Ideal source | Available today | Composition strategy (MVP) |
|-----|--------------|-----------------|----------------------------|
| Exams open / in marking | Exam counts by `status` | ⚠️ Partial | Client aggregate `GET /exams?per_page=100` |
| Marks submission % | Posting progress per class | ❌ | Proxy: matrix non-empty cells / `GET /marks/matrix` |
| Pending moderation | Exams in `moderation` | ⚠️ Partial | Filter `GET /exams?status=moderation` |
| Report cards draft vs published | RC counts by term | ❌ | Requires `GET /report-cards` school index |
| Lesson plans awaiting review | Review queue count | ✅ | `GET /lesson-plans/review-queue` total |
| Class mean / pass rate (term) | Analytics | ✅ | `GET /reports/exams/trends` (pick class) |
| CBC coverage % | Strand delivery | ❌ | Defer — show empty state |
| At-risk learners (academic) | Below threshold | ⚠️ Partial | `GET /reports/exams/student-insights` (class scoped) |

## 11.2 Recommended dedicated endpoint (post-MVP)

`GET /academics/summary?academic_year_id&term_id` returning:

```json
{
  "exams_by_status": { "marking": 3, "moderation": 1, "published": 12 },
  "marks_submission_percent": 78.5,
  "report_cards": { "draft": 120, "published": 400 },
  "lesson_plans_pending_review": 8,
  "coverage_percent": null
}
```

**No such endpoint exists** — mirror Finance workspace approach (client composition first).

---

# 12. Existing mobile-ready APIs

Summary of what can be wired **today** without Laravel changes:

| Capability | Endpoints | `@erp/core` status |
|------------|-----------|-------------------|
| Student academic summary | `/students/{id}/academic-summary` | ✅ Implemented |
| Student assessment history | `/students/{id}/assessment-history` | ✅ Implemented |
| Student report cards | `/report-cards?student_id=` + show | ✅ Implemented |
| Exam catalog | `/exams`, `/exams/{id}` | ⚠️ Legacy monolith only — extend `@erp/core` |
| Class marks read | `/marks`, `/marks/matrix` | ⚠️ Legacy monolith only |
| Exam analytics | `/reports/exams/*` | ❌ Not in `@erp/core` |
| Lesson plan moderation | `/lesson-plans/review-queue`, approve/reject | ❌ Not in `@erp/core` |
| Structure pickers | `/classes`, `/settings/*` | ✅ Partial (`useSettingsHub`) |
| Senior teacher scope | `/senior-teacher/*` | ❌ Not in `@erp/core` |

---

# 13. Missing APIs

| Priority | Endpoint / capability | Purpose | Work type |
|----------|----------------------|---------|-----------|
| **P0** | `GET /report-cards?classroom_id&term_id&status` | School-wide report card registry | New index on `ApiReportCardController` |
| **P0** | `GET /academics/summary` | Dashboard KPIs | New controller method |
| **P1** | `GET /exams` filters: `classroom_id`, `term_id`, `academic_year_id` | Exam registry | Extend existing |
| **P1** | `PUT /exams/{id}/status` | Moderation transitions | Wrap `Exam::canTransitionTo` |
| **P1** | `POST /exams/{id}/publish` | Publish to report cards | Wrap `ExamPublishingController` |
| **P1** | `POST /report-cards/generate` + `POST /report-cards/{id}/publish` | RC workflow | Wrap web actions |
| **P1** | Enrich `GET /report-cards` index | Real `overall_percentage`, `overall_grade` | Fix existing |
| **P1** | Expose `cbc` on `GET /report-cards/{id}` | CBC Hub / Student 360 | Extend show mapper |
| **P2** | `GET /curriculum-designs` | Curriculum workspace | New read API |
| **P2** | `GET /cbc/strands` (tree) | CBC Hub curriculum tab | New read API |
| **P2** | `GET /cbc/coverage` | Coverage matrix | New service + API |
| **P2** | `GET /portfolio-assessments` | Portfolio oversight | New read API |
| **P2** | `GET /assessments` (weekly) | School weekly assessment registry | New or extend facade |
| **P2** | `GET /academics/marks/progress` | Marks submission % by class | New aggregate |
| **P3** | Assessment Engine APIs (R4) | Unified formative/summative | Transformation epic |

---

# 14. Read-only MVP design

Replace `AcademicsScreen` placeholder with **AcademicsStackNavigator** (mirror Finance workspace).

## 14.1 Scope

- **Read-only** for marks and exams (no batch/matrix POST in Admin).
- **Moderation:** lesson-plan approve/reject only (existing API).
- **No** exam create, report-card generate/publish, curriculum upload, CBC edit.
- **Student drill-down** links to Student 360 Academics tab (reuse `@erp/core` hooks).

## 14.2 Workspace tree (MVP)

```
Academics (drawer / More)
└── AcademicsStackNavigator
    ├── AcademicsDashboardScreen
    ├── AssessmentsScreen          → student search → assessment history (read)
    ├── ExamsListScreen            → ExamDetailScreen
    ├── MarksScreen                → class/exam/subject pickers → class marks table (read)
    ├── ReportCardsScreen          → student search OR class filter (if API added)
    │   └── ReportCardDetailScreen
    ├── CbcHubScreen               → placeholder sections / performance levels read
    ├── CurriculumScreen           → empty state + web deep-link note
    └── ModerationScreen           → LessonPlanReviewList → LessonPlanDetail (approve/reject)
```

## 14.3 Screen → API / service / table / permission map (MVP)

| Screen | APIs (MVP) | Services | Tables | RBAC gate |
|--------|------------|----------|--------|-----------|
| **Dashboard** | `GET /exams`, `GET /lesson-plans/review-queue`, `GET /reports/exams/trends` (optional class) | Client aggregation | `exams`, `lesson_plans`, `exam_marks` | `academics.view` |
| **Assessments** | `GET /students` (search), `GET /students/{id}/assessment-history` | `AssessmentReadFacade` | `exam_marks`, `assessments`, `portfolio_assessments`, `report_cards` | `academics.view` |
| **Exams list** | `GET /exams`, `GET /settings/terms` | — | `exams`, `exam_types` | `academics.view` + `exams.view` |
| **Exam detail** | `GET /exams/{id}`, `GET /exams/{id}/marking-options` | — | `exams`, `exam_class_subject` | `exams.view` |
| **Marks** | `GET /marks`, `GET /marks/matrix`, `GET /marks/matrix/context` | `ClassSheetBuilder` (indirect) | `exam_marks` | `academics.view` + `exams.view` |
| **Report Cards** | `GET /report-cards?student_id=`, `GET /report-cards/{id}` | `ReportCardBatchService` | `report_cards` | `academics.view` + `report_cards.view` |
| **CBC Hub** | `GET /settings/grading` (labels); defer tree | — | `cbc_performance_levels`, `grading_schemes` | `academics.view` |
| **Curriculum** | — (empty) | — | `curriculum_designs` | `curriculum_designs.view` |
| **Moderation** | `GET /lesson-plans/review-queue`, show, approve, reject | `LessonPlanReviewNotification` | `lesson_plans` | `lesson_plans.view` + approve role |

## 14.4 Data layer (`@erp/core` — proposed)

```
types/academics.ts
academics/normalize.ts
api/academicsWorkspace.api.ts   // exams, marks, exam reports, lesson plans (extend student academics.api)
query/hooks/useAcademics.ts
queryKeys.academics.*
```

Reuse existing: `academicsApi` (student-scoped), `useSettingsHub`, `useInfiniteStudentList`.

## 14.5 Caching (TanStack Query)

| Key | staleTime | Notes |
|-----|-----------|-------|
| `academics.dashboard` | 60s | Composed KPIs |
| `academics.exams(filters)` | 45s | Filter in key |
| `academics.marks(exam,subject,class)` | 45s | Heavy — class scoped |
| `academics.lessonPlans.reviewQueue` | 30s | Invalidate on approve/reject |
| `students.{id}.assessment-history` | 60s | Shared with Student 360 |

## 14.6 MVP completion estimate

| Section | Without backend changes | Blocked on backend |
|---------|-------------------------|-------------------|
| Dashboard | ~60% (partial KPIs) | Accurate submission %, RC counts |
| Assessments | ~80% (student drill-down) | School registry |
| Exams | ~90% | Server-side filters |
| Marks | ~70% (read class sheets) | Progress aggregates |
| Report Cards | ~50% (student path only) | Class/term registry |
| CBC Hub | ~15% (empty/grading read) | All tree/coverage APIs |
| Curriculum | ~0% | Read API |
| Moderation | ~50% (lesson plans only) | Exam moderation API |

**Overall read-only MVP:** ~**55%** implementable immediately; ~**45%** needs thin backend wrappers.

---

# 15. Full workspace design

Target end-state after backend Phase 2 + selective write actions (approve/publish).

## 15.1 Workspace tree (full)

```
Academics
├── Dashboard                 KPIs, quick links, term/year filter
├── Assessments               School registry + student drill-down + type filters
├── Exams                     Registry, detail, schedules, status badges
├── Marks                     Class progress, matrix, moderation view (score_moderated)
├── Report Cards              Class/term registry, detail, PDF, publish queue
├── CBC Hub                   Curriculum tree, performance levels, coverage matrix, portfolios
├── Curriculum                Design library, parse status, AI assistant (governed)
└── Moderation                Lesson plans + exam status queue + scheme approvals
```

## 15.2 Screen → API / service / table / permission map (full)

| Screen | APIs (full) | Services | Tables | Permissions |
|--------|-------------|----------|--------|-------------|
| **Dashboard** | `GET /academics/summary`, `/reports/exams/trends`, `/lesson-plans/review-queue` | `AnalyticsService`, `TrendsService` | `exams`, `exam_marks`, `report_cards`, `lesson_plans` | `academics.view` |
| **Assessments registry** | `GET /assessments` or facade index, `GET /students/{id}/assessment-history` | `AssessmentReadFacade` → future `AssessmentEngine` | `assessment_events`* , `exam_marks`, `assessments`, `portfolio_assessments` | `academics.view` |
| **Exams** | `GET/POST/PUT /exams`, `PUT /exams/{id}/status`, `POST /exams/{id}/publish` | `ExamPublishingController`, `Exam` transitions | `exams`, `exam_sessions`, `exam_schedules` | `exams.view`, `exams.create`, `exams.approve`, `exams.publish` |
| **Marks** | `GET /marks`, `GET /marks/matrix`, `PUT /exam-marks/{id}/moderate`* | `ClassroomGradingService`, `ClassSheetBuilder` | `exam_marks` | `exams.view`, `exams.approve` |
| **Report Cards** | `GET /report-cards` (school index), show, `POST generate`, `POST publish`, PDF URL | `ReportCardBatchService`, `ReportCardAccessService` | `report_cards`, `report_card_skills` | `report_cards.view`, `.generate`, `.publish` |
| **CBC Hub — Curriculum** | `GET /cbc/curriculum`, `GET /cbc/performance-levels` | `CbcRationalizedSubjectSyncService` | `learning_areas`, `cbc_strands`, `cbc_substrands`, `cbc_performance_levels` | `cbc_strands.view`, `learning_areas.view` |
| **CBC Hub — Coverage** | `GET /cbc/coverage` | `SchemeOfWorkAutoGenerationService`* | `schemes_of_work`, `lesson_plans`, `cbc_substrands` | `schemes_of_work.view` |
| **CBC Hub — Portfolios** | `GET /cbc/portfolios` | — | `portfolio_assessments` | `portfolio_assessments.view` |
| **Curriculum** | `GET/POST /curriculum-designs`, progress, `POST /curriculum-assistant/chat` | `CurriculumParsingService`, `LLMService` | `curriculum_designs`, `curriculum_pages`, `curriculum_embeddings` | `curriculum_designs.view`, `curriculum_assistant.use` |
| **Moderation — Lessons** | review-queue, approve, reject | notifications | `lesson_plans` | `lesson_plans.view` |
| **Moderation — Exams** | `GET /exams?status=moderation`, `PUT status` | `Exam` workflow | `exams` | `exams.approve` |
| **Moderation — Schemes** | `GET /schemes-of-work?status=submitted`, approve | web controller wrap | `schemes_of_work` | `schemes_of_work.approve` |

\*Future Assessment Engine tables — not in production today.

## 15.3 Navigation & RBAC

| Layer | Rule |
|-------|------|
| Tab / drawer | `academics.view` (`ADMIN_TAB_PERMISSIONS.academics` in `@erp/core`) |
| Exams | `exams.view` |
| Marks oversight | `exams.view` (read); `exams.enter_marks` not required for Admin read-only |
| Report cards | `report_cards.view`; publish requires `report_cards.publish` |
| CBC config | `cbc_strands.view`, `learning_areas.view` |
| Curriculum | `curriculum_designs.view` |
| Moderation actions | `lesson_plans.view` + approver role; `exams.approve` for exam transitions |
| Senior Teacher | Server scopes via `getSupervisedClassroomIds()` — UI respects empty supervised list |

**Do not invent new permissions** — use `AcademicPermissionsSeeder` names.

## 15.4 Cross-links

| From | To |
|------|-----|
| Assessments row | Student 360 → Academics tab |
| Report card detail | Student 360 → Report Cards |
| Exam detail → class marks | Marks screen (pre-filled filters) |
| Class name | Filter Students registry by class |
| Dashboard KPI | Deep link to Moderation / Report Cards queue |

## 15.5 Explicitly out of scope (this workspace)

| Item | Home |
|------|------|
| Timetable generation / editing | Academics Structure (web) / Operations |
| Attendance marking | Staff App / Students |
| Homework capture | Staff App |
| Assessment Engine (R4) | `assessment-engine-design.md` transformation |
| KNEC/KPSEA export (E13) | Future national assessment module |
| TPAD / staff appraisal | People |
| Top-level Reports (board pack) | Reports area (IA §1) |

## 15.6 Implementation phasing

| Phase | Deliverable | Backend |
|-------|-------------|---------|
| **A — Read-only MVP** | Dashboard, Exams, Marks (read), Assessments (student), Report Cards (student), Moderation (lesson plans) | None |
| **B — Oversight APIs** | Report card class registry, academics summary, exam filters | Thin wrappers on existing controllers |
| **C — Workflow APIs** | Exam moderation, RC generate/publish, scheme approve | POST/PUT mirrors of web routes |
| **D — CBC & Curriculum** | CBC Hub, Curriculum library | New read APIs + coverage service |
| **E — Transformation** | Unified assessments, competency-first | Assessment Engine (R4) |

## 15.7 Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Two parallel assessment systems | High | Present Exams + facade history clearly; don't label weekly assessments as "CBC formative" until E10 |
| No school-wide RC API | High | Phase B priority; student-only path in Phase A |
| `/marks` returns full class payloads | Medium | Cache by exam+class; never fetch per student via N+1 |
| CBC not in mobile report-card JSON | High | Phase B extend show; CBC Hub stays honest empty state until then |
| Exam list lacks server filters | Medium | Client filter + Phase B query params |
| Moderation split (lesson API vs exam web) | Medium | Moderation screen tabs with clear "exam — web only" until Phase C |
| Principal publish journey incomplete on mobile | High | Phase C workflow APIs or deep-link to web for publish |
| `academics.view` vs granular permissions | Low | Sub-feature gates with `exams.view`, `report_cards.view`, etc. |

## 15.8 Alignment with product backlog

| Backlog item | This workspace |
|--------------|----------------|
| E7.3.2 Exam/result publishing controls | Phase C — Moderation + Report Cards |
| E10 CBC Assessment Engine | Phase E — not MVP |
| E11 Curriculum library | Phase D — Curriculum + CBC Hub |
| E12 Portfolio | Phase D — CBC Hub portfolios |
| E27.2.2 CBC analytics | Phase D — coverage API |
| Student 360 Academics | Reuse Phase 0 facade — no duplication |

---

## Appendix A — Permission reference (academic module)

From `AcademicPermissionsSeeder` (representative):

| Group | Permissions |
|-------|-------------|
| Structure | `classrooms.*`, `subjects.*`, `learning_areas.*` |
| CBC | `cbc_strands.*`, `cbc_substrands.*`, `competencies.*` |
| Schemes | `schemes_of_work.*` |
| Lesson plans | `lesson_plans.*` |
| Exams | `exams.*`, `exam_types.*` |
| Portfolios | `portfolio_assessments.*` |
| Report cards | `report_cards.*` |
| Homework | `homework.*` |
| Curriculum | `curriculum_designs.*`, `curriculum_assistant.use` |
| Admin tab | `academics.view` (`@erp/core` `AdminPermission.ACADEMICS_VIEW`) |

---

## Appendix B — `@erp/core` reuse inventory

| Asset | Path | Reuse in workspace |
|-------|------|-------------------|
| Student academics API | `packages/core/src/api/academics.api.ts` | Assessments, Report Cards (student) |
| Student academics hooks | `packages/core/src/query/hooks/useStudentAcademics.ts` | Same |
| Settings hub | `useSettingsHub` | Term/year/class/subject pickers |
| Student search | `useInfiniteStudentList` | Assessments, Report Cards |
| Legacy staff client | `mobile-app/src/api/academics.api.ts` | Reference for exams/marks/reports port |
| Navigation area | `config/navigation.ts` `academics` | Update sections to match workspace tree |
| Placeholder | `apps/admin/src/features/academics/screens/AcademicsScreen.tsx` | Replace with stack |

---

## Appendix C — Decision log

| Question | Decision |
|----------|----------|
| Canonical exam registry? | `GET /exams` + future filters |
| Canonical marks oversight? | `GET /marks` (class) + `GET /marks/matrix` (progress) |
| Canonical assessments? | Student: `assessment-history`; School: **needs new API** |
| Canonical report cards? | Student: existing; School: **needs class/term index** |
| Canonical CBC? | **Defer** until read APIs; expose RC `cbc` block first |
| Canonical moderation (MVP)? | **Lesson plans API only** |
| Admin captures marks? | **No** — Staff App |
| Assessment Engine in MVP? | **No** — R4 transformation |

---

*End of audit — ready for Academics Workspace implementation planning.*
