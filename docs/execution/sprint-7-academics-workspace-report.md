# Sprint 7 — Academics Workspace MVP

**Date:** 2026-06-04  
**Scope:** Replace Academics placeholder with read-only oversight workspace (Dashboard, Assessments, Exams, Marks, Report Cards, Moderation) in `@erp/admin`.  
**Out of scope:** Assessment Engine, CBC Hub, Curriculum, mark entry, exam create/publish, report-card generate/publish, KNEC.

**Academics Workspace completion: 100%** of defined MVP scope (6 workspace areas + navigation + data layer + RBAC).

---

## 1. APIs used

All endpoints are existing Laravel Sanctum routes — **no backend changes**.

| Area | Method | Path |
|------|--------|------|
| Dashboard | `GET` | `/exams` (status aggregation, `per_page=100`) |
| Dashboard | `GET` | `/lesson-plans/review-queue` (pending count) |
| Dashboard | `GET` | `/reports/exams/trends` (first class + current term) |
| Dashboard | `GET` | `/settings/academic-years`, `/settings/terms`, `/settings/classes` |
| Assessments | `GET` | `/students` (search) |
| Assessments | `GET` | `/students/{id}/academic-summary` |
| Assessments | `GET` | `/students/{id}/assessment-history` |
| Exams | `GET` | `/exams`, `/exams/{id}`, `/exams/{id}/marking-options` |
| Marks | `GET` | `/marks`, `/marks/matrix`, `/marks/matrix/context` |
| Report Cards | `GET` | `/report-cards?student_id=`, `/report-cards/{id}` |
| Moderation | `GET` | `/lesson-plans/review-queue`, `/lesson-plans/{id}` |
| Moderation | `POST` | `/lesson-plans/{id}/approve`, `/lesson-plans/{id}/reject` |
| Supporting | `GET` | `/settings/classes`, `/settings/academic-years`, `/settings/terms` |

---

## 2. APIs added

**None.**

---

## 3. Files created

### `@erp/core`

| File | Role |
|------|------|
| `types/academics.ts` | Exam, marks, matrix, dashboard, filter types |
| `api/academicsWorkspace.api.ts` | Workspace REST client |
| `academics/normalize.ts` | Exam, mark, lesson-plan normalizers |
| `academics/fetchAcademicDashboard.ts` | Client-composed dashboard KPIs |
| `academics/index.ts` | Barrel |
| `query/hooks/useAcademicsWorkspace.ts` | TanStack hooks + moderation mutations |

### `@erp/ui`

| File | Role |
|------|------|
| `academics/types.ts` | Presentational prop types |
| `academics/AcademicKpiCard.tsx` | KPI tile |
| `academics/AcademicTrendCard.tsx` | Trend summary chip |
| `academics/AcademicScreenHeader.tsx` | Section header |
| `academics/AcademicSearchBar.tsx` | Search input |
| `academics/ExamStatusBadge.tsx` | Status chip |
| `academics/ExamListItem.tsx` | Exam row |
| `academics/ExamFilters.tsx` | Status filter chips |
| `academics/MarksRow.tsx` | Class sheet row |
| `academics/MarksMatrixRow.tsx` | Matrix row |
| `academics/ReportCardCard.tsx` | Report card list item |
| `academics/AssessmentCard.tsx` | Assessment history row |
| `academics/ModerationCard.tsx` | Lesson plan queue row |
| `academics/index.ts` | Barrel |

### `apps/admin`

| File | Role |
|------|------|
| `navigation/academicsStackTypes.ts` | Stack param list |
| `navigation/AcademicsStackNavigator.tsx` | Academics stack |
| `features/academics/screens/AcademicsDashboardScreen.tsx` | Dashboard |
| `features/academics/screens/AssessmentsScreen.tsx` | Student search |
| `features/academics/screens/AssessmentHistoryScreen.tsx` | Timeline + filters |
| `features/academics/screens/AssessmentDetailScreen.tsx` | Assessment detail |
| `features/academics/screens/ExamsListScreen.tsx` | Exam registry |
| `features/academics/screens/ExamDetailScreen.tsx` | Exam detail |
| `features/academics/screens/MarksScreen.tsx` | Class sheet |
| `features/academics/screens/MarksMatrixScreen.tsx` | Students × exams matrix |
| `features/academics/screens/ReportCardsScreen.tsx` | Student search |
| `features/academics/screens/ReportCardHistoryScreen.tsx` | Term cards list |
| `features/academics/screens/ReportCardDetailScreen.tsx` | Full card |
| `features/academics/screens/ModerationScreen.tsx` | Review queue |
| `features/academics/screens/LessonPlanReviewScreen.tsx` | Approve/reject |
| `features/academics/screens/LessonPlanDetailScreen.tsx` | Re-export of review screen |
| `features/academics/hooks/useExamsRegistryState.ts` | Exam filters |
| `features/academics/hooks/useMarksRegistryState.ts` | Marks pickers |
| `features/academics/hooks/useModerationRegistryState.ts` | Queue filters |
| `features/academics/utils/formatters.ts` | Display helpers |

---

## 4. Files modified

| File | Change |
|------|--------|
| `packages/core/src/api/index.ts` | Export `academicsWorkspaceApi` |
| `packages/core/src/index.ts` | Export academics module |
| `packages/core/src/types/index.ts` | Export academics types |
| `packages/core/src/query/index.ts` | Export workspace hooks |
| `packages/core/src/query/queryKeys.ts` | `academics.*` key tree |
| `packages/ui/src/index.ts` | Export academics UI |
| `apps/admin/src/navigation/DrawerNavigator.tsx` | Academics → `AcademicsStackNavigator` |
| `apps/admin/src/features/academics/index.ts` | Screen barrel |

### Removed

| File | Reason |
|------|--------|
| `apps/admin/src/features/academics/screens/AcademicsScreen.tsx` | Placeholder replaced |

---

## 5. Architecture decisions

1. **Mirror Finance workspace** — `AcademicsStackNavigator` in drawer, dashboard + section quick links, `@erp/core` data layer + `@erp/ui` primitives.
2. **Student-scoped paths** for Assessments and Report Cards — no school-wide registry API exists; student search → history/detail (same as audit recommendation).
3. **Client-side exam filters** — `GET /exams` lacks `term_id`/`classroom_id` server filters; applied after fetch in `useInfiniteExams`.
4. **Dashboard composition** — no `/academics/summary`; KPIs from exams list + review-queue total + optional trends for first class/term.
5. **Reuse Student 360 hooks** — `useStudentAcademicSummary`, `academicsApi` assessment/report-card endpoints shared via workspace hooks.
6. **Lesson plan type** — reuses `LessonPlanRecord` from `types/approval.ts` (same API shape as Approval Center).
7. **Moderation only** — exam status transitions not exposed via API; lesson-plan approve/reject only write path.

---

## 6. Query strategy

```
queryKeys.academics
├── dashboard()                         → useAcademicDashboard
├── exams(filters)                      → useInfiniteExams (alias useExams)
├── examDetail(id)                      → useExamDetail
├── examMarkingOptions(id)              → useExamMarkingOptions
├── marks(filters)                      → useMarks
├── marksMatrixContext(classroomId)     → useMarksMatrixContext
├── marksMatrix(filters)                → useMarksMatrix
├── assessmentHistory(studentId, filters) → useAssessmentHistory
├── reportCards(studentId)              → useReportCards
├── reportCardDetail(id)                → useReportCardDetail
├── moderationQueue(filters)            → useModerationQueue
└── lessonPlanDetail(id)                → useLessonPlanDetail
```

| Hook | staleTime | Notes |
|------|-----------|-------|
| Dashboard | 60s | 3–5 parallel calls |
| Exam list | 45s | Infinite; client filter in key |
| Marks / matrix | 45s | Class-scoped |
| Assessment history | 45s | Infinite; shares facade with Student 360 |
| Report cards | 60s | Per student |
| Moderation queue | 30s | Invalidates `academics.all` on approve/reject |

---

## 7. RBAC mapping

| Gate | Permission |
|------|------------|
| Workspace / Dashboard | `academics.view` |
| Exams / Marks | `academics.view` + `exams.view` |
| Report Cards | `academics.view` + `report_cards.view` |
| Moderation | `academics.view` + `lesson_plans.view` |

Sections hidden when sub-permission missing. Server-side scope (teacher classrooms, reviewer scope) remains authoritative.

---

## 8. Known limitations

| Limitation | Impact |
|------------|--------|
| No school-wide report-card index | Report Cards requires student search |
| Exam list server filters missing | Term/year/class filtered client-side on fetched pages |
| Dashboard KPIs from first 100 exams | May under-count in large schools |
| Trends widget uses first class only | May not match user's focus class |
| Marks API omits grade/performance level | Class sheet shows score and % only |
| Report card index zeros %/grade | Detail screen (`show`) used for real metrics |
| Exam moderation not in API | Moderation is lesson-plan queue only |
| Matrix shows open/marking exams only | Per backend `marks/matrix` query |

---

## 9. Future backend dependencies

| Priority | Endpoint / change |
|----------|-------------------|
| P0 | `GET /academics/summary` — accurate dashboard KPIs |
| P0 | `GET /report-cards?classroom_id&term_id` — school registry |
| P1 | `GET /exams` server filters (`term_id`, `classroom_id`, `academic_year_id`) |
| P1 | Enrich `GET /report-cards` index with real percentages |
| P1 | `PUT /exams/{id}/status` — exam moderation in mobile |
| P2 | `GET /cbc/*`, `GET /curriculum-designs` — CBC Hub & Curriculum sections |
| Future | Assessment Engine (R4) — unified assessment history |

---

## 10. Verification

```bash
cd mobile-app/packages/core && npx tsc --noEmit
cd mobile-app/packages/ui && npx tsc --noEmit
cd mobile-app/apps/admin && npm run typecheck
```

**Result:** All three pass with zero errors.

---

## 11. Navigation structure

```
Academics (drawer)
└── AcademicsStackNavigator
    ├── AcademicsDashboard
    ├── Assessments → AssessmentHistory → AssessmentDetail
    ├── ExamsList → ExamDetail
    ├── Marks → MarksMatrix
    ├── ReportCards → ReportCardHistory → ReportCardDetail
    └── Moderation → LessonPlanReview
```
