# Student 360 — Academic Data Source Audit (Sprint 3 Batch 3 Discovery)

**Date:** 2026-06-04  
**Role:** ERP Architect · Mobile Architect · Academic Systems Consultant  
**Scope:** Read-only audit of existing Laravel Sanctum APIs. **No code changes. No new endpoints.**

This document informs the **Student 360 → Academics** tab (Batch 4+) for the Admin App. It answers: what exists today, what is mobile-suitable, and the **smallest API surface** to power Overview, Assessment History, Report Cards, and Performance Trends.

---

## 1. Executive summary

| Finding | Implication |
| --- | --- |
| There is **no** `GET /students/{id}/marks` (or equivalent) listing a single student’s exam marks across time. | Assessment history must be composed from **report cards** and/or **class-scoped** exam report payloads. |
| **CBC** exists in the data model (`ExamMark.performance_level_id`, `ReportCardBatchService` CBC block) but has **no dedicated mobile API**. | CBC UI is **out of scope** until a read API exists or report-card show is extended. |
| **“Assessments”** in the product sense map to **exam marks** (`exam_marks`), not a separate assessment resource. | Treat homework (`/assignments`) and academic-report forms (`/academic-reports`) as **adjacent**, not core Student 360 academics. |
| **Report cards** are the only student-scoped, read-optimized academic aggregate. | They should anchor **Report Cards** and much of **Academic Overview**. |
| **Exam reports** (`/reports/exams/*`) are **class/term scoped**; student drill-down is by **filtering `rows[].student_id`** in class-sheet payloads. | Use sparingly (1 term sheet call), not per exam. |

**Recommended minimal mobile surface (4 read calls + student context):**

1. `GET /students/{id}/stats` — headline academic KPI (`exam_average`, when populated).  
2. `GET /report-cards?student_id={id}` — term card index.  
3. `GET /report-cards/{id}` — full term detail (subjects, skills, grades, comments).  
4. `GET /reports/exams/class-sheet?mode=term&academic_year_id=&term_id=&classroom_id=[&stream_id=]` — extract **one row** for positions / subject grid when report card is draft or missing.

Optional context (not student-specific): `GET /exams` filtered by term/class for labels only.

---

## 2. Endpoint inventory

All routes below are under **`/api/*`**, middleware **`auth:sanctum`**, unless noted.

### 2.1 Marks endpoints

| Route | Method | Controller | Purpose |
| --- | --- | --- | --- |
| `/marks` | GET | `ApiAcademicsController::marks` | Marks for **one exam + subject + classroom** (all students in class). |
| `/exam-marks/batch` | POST | `ApiAcademicsController::batchMarks` | Teacher mark entry (write). |
| `/marks/matrix/context` | GET | `ApiAcademicsController::marksMatrixContext` | Classrooms, exam types, streams for matrix UI. |
| `/marks/matrix` | GET | `ApiAcademicsController::marksMatrix` | Class matrix: students × exams. |
| `/exam-marks/matrix/batch` | POST | `ApiAcademicsController::batchMarksMatrix` | Matrix write. |

#### `GET /marks`

| Attribute | Detail |
| --- | --- |
| **Required query** | `exam_id`, `subject_id`, `classroom_id` |
| **Permissions** | Teacher-like: `canTeacherAccessClassroom(classroom_id)`; teacher student scope on query. Admin/Secretary/Super Admin: full class. Academic Administrator: **cannot** enter marks (POST only); read not explicitly blocked. |
| **Payload** | Paginated envelope with `data[]`: `{ id, exam_id, student_id, student_name, subject_id, marks, total_marks, remarks, percentage, created_at, updated_at }` |
| **Student 360 suitability** | **Low** for Student 360 — requires known exam/subject/class; returns **whole class**, not one student. Mobile must filter `student_id` client-side. |
| **CBC fields** | Not exposed in API response (stored on `ExamMark`: `performance_level_id`, `competency_scores`, etc.). |

**Gap:** No list-by-`student_id` marks endpoint.

---

### 2.2 Exam endpoints

| Route | Method | Controller | Purpose |
| --- | --- | --- | --- |
| `/exams` | GET | `ApiAcademicsController::exams` | Paginated exam catalog. |
| `/exams/{id}` | GET | `ApiAcademicsController::showExam` | Exam detail (+ names when `detail`). |
| `/exams/{id}/marking-options` | GET | `ApiAcademicsController::examMarkingOptions` | Class/subject combos for marking. |

#### `GET /exams`

| Attribute | Detail |
| --- | --- |
| **Query** | `status`, `per_page` (no `student_id`, `classroom_id` in controller — filter client-side or extend later). |
| **Permissions** | Teacher-like: exams limited to assigned classrooms (incl. `exam_class_subject` pivot). |
| **Payload item** | `{ id, name, exam_type_id, exam_type_name, academic_year_id, term_id, classroom_id, stream_id, subject_id, start_date, end_date, status, total_marks, created_at, updated_at }` |
| **Mobile suitability** | **Medium** — context for timelines and linking to class-sheet `mode=exam`; not a student summary. |

---

### 2.3 Assessment endpoints (naming vs reality)

There is **no** `/assessments` API. In this codebase:

| Concept | Actual API | Student-scoped? |
| --- | --- | --- |
| Exam marks | `/marks`, `/marks/matrix`, exam reports class-sheet | No (class/exam scoped) |
| Homework / class assignments | `/assignments` (`ApiHomeworkController`) | No (`classroom_id`, `subject_id`, `teacher_id`) |
| Teacher qualitative forms | `/academic-reports/*` (`ApiAcademicReportsController`) | No (template assignments to staff/roles) |
| Portfolio / CAT (CBC) | **Web only** (`portfolio-assessments` routes) | N/A for mobile |

#### `GET /assignments` (homework)

| Attribute | Detail |
| --- | --- |
| **Query** | `classroom_id`, `subject_id`, `status` (`active`/`closed`), `search`, `per_page` |
| **Permissions** | Teacher: assigned classes; Admin/Secretary: broader. |
| **Payload** | Homework items (title, due_date, classroom, subject) — **not student scores**. |
| **Mobile suitability** | **Low** for Student 360 Academics (Batch 4 scope excludes homework marks). |

#### `/academic-reports/*`

Template builder + staff submissions. **Not** learner assessment history. Exclude from Student 360 academics minimal surface.

---

### 2.4 Report card endpoints

| Route | Method | Controller | Purpose |
| --- | --- | --- | --- |
| `/report-cards` | GET | `ApiReportCardController::index` | Paginated cards for **one student**. |
| `/report-cards/{id}` | GET | `ApiReportCardController::show` | Full card for mobile/PDF parity. |

#### `GET /report-cards?student_id={id}`

| Attribute | Detail |
| --- | --- |
| **Required query** | `student_id` |
| **Optional** | `page`, `per_page` (max 50) |
| **Permissions** | `assertUserCanAccessStudent`: teacher scope, parent/guardian `canAccessStudent`. |
| **Parent/Guardian** | Only `published_at` not null. |
| **Payload row** | `{ id, student_id, student_name, class_id, class_name, term_id, academic_year_id, overall_marks: 0, overall_percentage: 0, overall_grade: null, status: published\|draft, generated_at, subjects: [], created_at, updated_at }` |
| **Mobile suitability** | **High** for list/navigation; **index omits aggregates** (percentages hard-coded to 0). Use **show** for real marks/grades. |

#### `GET /report-cards/{id}`

| Attribute | Detail |
| --- | --- |
| **Permissions** | Same student access; parents blocked if unpublished. |
| **Source** | `ReportCardBatchService::build($id)` mapped to mobile shape. |
| **Payload (high level)** | `{ id, student_id, student_name, class_id, class_name, term_id, academic_year_id, overall_percentage, overall_grade, subjects[], skills[], teacher_comment, principal_comment, status, generated_at, ... }` |
| **Subject row** | `{ subject_id, subject_name, marks, total_marks, percentage, grade, remarks, position }` (term averages from batch builder) |
| **Skills** | Co-curricular / skill ratings (`excellent` \| `good` \| `average` \| `needs_improvement`) |
| **CBC** | Computed in DTO (`cbc` array) but **not included** in mobile JSON — only `overall_grade` may reflect `overall_performance_level_name`. |
| **Mobile suitability** | **Excellent** — canonical term report for Student 360. |

---

### 2.5 CBC endpoints

| Route | Mobile API? |
| --- | --- |
| Dedicated CBC read APIs | **None** |
| CBC curriculum admin | Web: `cbc-strands`, `cbc-substrands`, etc. |
| CBC on marks | DB: `ExamMark.performance_level_id`, `competency_scores`, `assessment_method`, `cat_number` |
| CBC on report cards | `ReportCardBatchService` → `cbc` block (not fully exposed on mobile `show`) |
| Lesson plans | `substrand_id`, `core_competencies` on write — not student 360 |

**Mobile suitability:** **Not ready** for a CBC tab without API extension.

---

### 2.6 Academic summary endpoints

| Route | Method | Controller | Student-scoped? | Summary use |
| --- | --- | --- | --- | --- |
| `/students/{id}/stats` | GET | `ApiStudentController::stats` | **Yes** | Attendance %, optional `fees_balance`, `exam_average` (often null) |
| `/reports/exams/class-sheet` | GET | `ApiExamReportsController::classSheet` | No — **class/term** | Rich per-student row in `rows[]` |
| `/reports/exams/student-insights` | GET | `ApiExamReportsController::studentInsights` | No — **class/exam** | Top 10 / most improved lists |
| `/reports/exams/trends` | GET | `ApiExamReportsController::trends` | No — class/term series | Class mean per exam |
| `/reports/exams/insights` | GET | `ApiExamReportsController::insights` | No | Text insights from trends |
| `/reports/exams/mastery-profile` | GET | `ApiExamReportsController::masteryProfile` | No | Class mastery |
| `/reports/exams/subject-performance` | GET | `ApiExamReportsController::subjectPerformance` | No | Class subject stats |
| `/reports/exams/teacher-performance` | GET | `ApiExamReportsController::teacherPerformance` | No | Staff analytics |

#### `GET /students/{id}/stats` (academic fields)

```json
{
  "attendance_percentage": 92.5,
  "expected_school_days": 45,
  "attendance_records_count": 40,
  "exam_average": null,
  "fees_balance": 12000.00
}
```

| Mobile suitability | **Medium** — good headline KPI when `exam_average` is populated; not subject breakdown. |

#### `GET /reports/exams/class-sheet`

| Attribute | Detail |
| --- | --- |
| **Query** | `mode`: `exam` \| `term` (default `exam`). **Exam:** `exam_id`, `classroom_id`, optional `stream_id`. **Term:** `academic_year_id`, `term_id`, `classroom_id`, optional `stream_id`. |
| **Permissions** | `ExamReportsAccess::assertClassroomAccess` — Admin/Secretary/Super Admin full; teachers assigned/supervised classes. |
| **Payload** | `{ meta, subjects[], rows[] }` where each **row** includes: `student_id`, `admission_number`, `name`, `subject_scores{subjectId: score}`, `total`, `average`, `position`, `class_position`, `stream_position`, `subject_positions` |
| **Mobile suitability** | **High** for extracting **one student** from `rows` for term grid/positions; **heavy** payload (full class). Cache aggressively. |

#### `GET /reports/exams/student-insights`

Requires `exam_id` + `classroom_id`. Returns `top_students` and `most_improved` (arrays of class learners). **Not** single-student drill-down unless client finds row by `student_id` in a separate class-sheet call.

| Mobile suitability | **Low** for Student 360 per-student view. |

#### `GET /reports/exams/trends` + `/insights`

Term-level **class/stream** exam series: `{ exam_id, exam, mean, pass_rate, delta_mean, ... }`. No per-student series.

| Mobile suitability | **Low** for individual performance trends; useful for **class context** only. |

---

## 3. Permission model (cross-cutting)

| Actor | Exams / marks read | Report cards | Exam reports (`/reports/exams/*`) |
| --- | --- | --- | --- |
| Super Admin, Admin, Secretary | Broad | All students (with access rules) | Full school (`userHasFullAccess`) |
| Teacher / Senior Teacher | Assigned classes + student filter | Students in scope | Assigned/supervised `classroom_id` only |
| Parent / Guardian | Linked students | **Published** report cards only | Not typical |
| Academic Administrator | Mark entry blocked (POST) | Per student access rules | Per classroom access |

**Admin App RBAC:** Gate UI with `academics.view` (preset on Admin, Secretary, Academic Admin, etc.). Align with server checks — server always wins on classroom/student scope.

---

## 4. Canonical source recommendations

### 4.1 Academic Overview (Student 360 tab)

**Primary**

| Source | Why |
| --- | --- |
| `GET /report-cards/{latestPublishedId}` | Richest term snapshot: subjects, overall %, grade, skills, comments. |
| `GET /students/{id}/stats` | Single-number `exam_average` + pairs with attendance block on Overview. |

**Secondary (enrichment)**

| Source | Why |
| --- | --- |
| `GET /reports/exams/class-sheet?mode=term&...` | Class/stream **position** and live subject scores if report card unpublished or stale. |

**Do not use as overview primary:** `GET /marks` (class-only), `GET /exams` (catalog), homework, academic-reports.

---

### 4.2 Assessment History

Interpret as **chronological evidence of summative assessment** (exams → report cards), not homework.

| Source | Why |
| --- | --- |
| `GET /report-cards?student_id=` | Term-level index (dates, status, term/year). |
| `GET /report-cards/{id}` | Per-term subject breakdown (assessment history **by term**). |

**Within-term exam-level history (gap):**

- No student marks list API.  
- **Workaround (no new route):** For each exam in term, `GET /reports/exams/class-sheet?mode=exam&exam_id=&classroom_id=` and pick `rows[]` where `student_id` matches — **N+1 calls**, class-sized payloads → **not recommended** for mobile v1.  
- **Pragmatic v1:** Show **term cards** as history; drill into report card detail for subject-level marks.

---

### 4.3 Report Cards

| Source | Why |
| --- | --- |
| `GET /report-cards?student_id=` | List UI |
| `GET /report-cards/{id}` | Detail UI (PDF parity / full grades) |

**Note:** Fix or compensate for index returning `overall_percentage: 0` — mobile should use **show** or class-sheet row for display metrics until index is enriched (backend change, out of discovery scope).

---

### 4.4 Performance Trends

| Source | Why |
| --- | --- |
| **Multi-term:** `GET /report-cards/{id}` across terms | Compare `overall_percentage` / `overall_grade` term-over-term (requires multiple show calls or enriched index). |
| **Within-term subject trend:** `GET /report-cards/{id}` → `subjects[].percentage` | Subject sparklines/table. |
| **Positions trend:** `GET /reports/exams/class-sheet?mode=term` per term | Extract `position` / `class_position` from student row. |

**Not canonical for student trends:** `/reports/exams/trends` (class aggregate means), `/reports/exams/student-insights` (leaderboard slices).

---

## 5. Smallest API surface (mobile module design)

```text
Student360AcademicsModule
├── useStudentStats(studentId)           → GET /students/{id}/stats
├── useStudentReportCards(studentId)     → GET /report-cards?student_id=
├── useStudentReportCard(cardId)         → GET /report-cards/{id}
└── useTermClassSheetRow(student, term)  → GET /reports/exams/class-sheet?mode=term&...
         └── enabled only if: card missing OR user needs live positions
```

**TanStack Query keys (suggested):**

- `['students', id, 'stats']`
- `['students', id, 'report-cards', filters]`
- `['report-cards', cardId]`
- `['exam-reports', 'class-sheet', 'term', year, termId, classroomId, streamId]` — shared per class/term, extract row client-side

**Caching:** Report cards and stats `staleTime` ~60s; class-sheet term is heavier — `staleTime` 2–5 min, keyed by class+term not student.

**Invalidation:** After mark entry (staff app) — not Admin v1; optional pull-to-refresh on Academics tab.

---

## 6. Payload reference (mobile-oriented)

### Class-sheet student row (term mode)

```json
{
  "student_id": 42,
  "admission_number": "ADM-001",
  "name": "Jane Doe",
  "subject_scores": { "3": 78, "5": 82 },
  "total": 160,
  "average": 80,
  "subjects_taken": 2,
  "position": 5,
  "class_position": 5,
  "stream_position": null,
  "subject_positions": { "3": 4, "5": 2 }
}
```

### Report card show (abbreviated)

```json
{
  "id": 12,
  "student_id": 42,
  "term_id": 2,
  "academic_year_id": 1,
  "overall_percentage": 74.5,
  "overall_grade": "Meeting Expectations",
  "subjects": [
    {
      "subject_name": "Mathematics",
      "percentage": 78,
      "grade": "B+",
      "remarks": "Good progress"
    }
  ],
  "skills": [{ "skill_name": "Leadership", "rating": "good" }],
  "status": "published"
}
```

---

## 7. Gaps and risks

| Risk | Severity | Mitigation (mobile, no new routes) |
| --- | --- | --- |
| No `student_id` marks list | **High** | Term history via report cards; defer exam-by-exam timeline. |
| Report card index zeros for %/grade | **Medium** | Always open **show** for metrics; show list as status/date only. |
| CBC not in API | **High** | Omit CBC tab (per product direction). |
| Class-sheet payload size | **Medium** | One call per term; cache by class+term; extract one row. |
| `exam_average` often null in stats | **Medium** | Derive from latest report card `overall_percentage`. |
| Parent-only published cards | **Low** | Admin sees drafts; handle 403 on unpublished for parent apps only. |
| Exam reports classroom_id required | **Medium** | From `StudentDetail.classroomId` / `streamId`. |

---

## 8. Future backend candidates (out of scope for discovery)

Only if mobile orchestration proves insufficient:

| Candidate | Purpose |
| --- | --- |
| `GET /students/{id}/marks` | Paginated exam marks history with subject/exam metadata |
| `GET /students/{id}/academics/summary` | Single composed overview (stats + latest card + trend) |
| Enrich `GET /report-cards` index | Real `overall_percentage`, `overall_grade` |
| Expose `cbc` on report-card show | CBC tab without new routes |

**Discovery directive:** Do **not** implement these until Batch 4+ product sign-off.

---

## 9. Alignment with Student 360 batches

| Batch | Scope | This audit |
| --- | --- | --- |
| Sprint 3 Batch 1 | Registry | — |
| Sprint 3 Batch 2 | Overview, Attendance, Fees, Family | Uses `/students/{id}`, stats, statement — **no conflict** |
| Sprint 3 Batch 3 | **This discovery** | — |
| Sprint 4 (proposed) | Academics tab | Implement using §4–§5 minimal surface |

**Explicitly excluded (per requirements):** CBC, Health, Discipline, Transport, Documents, Academics homework workflows, teacher academic-report forms.

---

## 10. Decision log

| Question | Decision |
| --- | --- |
| Canonical academic overview? | **Report card show** + **student stats** |
| Canonical assessment history? | **Report cards** (list + show per term) |
| Canonical report cards? | **`/report-cards`** index + show |
| Canonical performance trends? | **Report cards across terms** (multi-show); optional **term class-sheet** for positions |
| Canonical marks API? | **None today** — do not fake via `/marks` |
| Canonical CBC API? | **None** — defer |
| Canonical assessments API? | **N/A** — exam marks + report cards |

---

## 11. References

| Asset | Path |
| --- | --- |
| API routes | `routes/api.php` |
| Academics controller | `app/Http/Controllers/Api/ApiAcademicsController.php` |
| Report cards | `app/Http/Controllers/Api/ApiReportCardController.php` |
| Exam reports | `app/Http/Controllers/Api/ApiExamReportsController.php` |
| Class sheet builder | `app/Services/Academics/ExamReports/ClassSheetBuilder.php` |
| Mobile client (staff) | `mobile-app/src/api/academics.api.ts` |
| Admin RBAC | `mobile-app/packages/core/src/rbac/permissions.ts` (`academics.view`) |

---

*End of audit — ready for Sprint 4 implementation planning.*
