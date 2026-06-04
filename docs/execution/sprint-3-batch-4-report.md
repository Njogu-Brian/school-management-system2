# Sprint 3 — Batch 4 Report: Student 360 Academics

**Status:** Complete  
**Scope:** Read-only Academics tab on Student 360 (Admin App), powered by Phase 0 assessment APIs and existing report card endpoints. No CBC UI, no assessment creation, no new backend routes.

**Verification:** `tsc --noEmit` passes for `packages/core`, `packages/ui`, and `apps/admin`.

---

## 1. Summary

The **Academics** tab is added to the existing Student 360 shell when the user has `academics.view`. It shows:

| Section | Source |
| --- | --- |
| Academic overview | `GET /students/{id}/academic-summary` + latest `GET /report-cards/{id}` for position |
| Performance trend | Client-derived from assessment history (`report_card_term` rows preferred) |
| Assessment filters | Category chips + optional subject chips (from loaded history) |
| Assessment timeline | `GET /students/{id}/assessment-history` (infinite scroll) |
| Report card history | `GET /report-cards?student_id=` + history % when available |
| Report card detail | `GET /report-cards/{id}` (read-only stack screen) |

---

## 2. Files created

| File | Purpose |
| --- | --- |
| `packages/core/src/types/studentAcademics.ts` | API + normalized academic types |
| `packages/core/src/api/academics.api.ts` | Summary, history, report card clients |
| `packages/core/src/students/academics.ts` | Type mapping, trend builders, filter helpers |
| `packages/core/src/query/hooks/useStudentAcademics.ts` | TanStack Query hooks |
| `packages/ui/src/student360/academics/*` | Overview card, filters, timeline, trend, report list |
| `apps/admin/.../tabs/AcademicsTab.tsx` | Tab composition + data wiring |
| `apps/admin/.../screens/ReportCardDetailScreen.tsx` | Read-only report card drill-down |
| `docs/execution/sprint-3-batch-4-report.md` | This report |

---

## 3. Files modified

| File | Change |
| --- | --- |
| `packages/core/src/types/student360.ts` | `Student360TabId` includes `academics` |
| `packages/core/src/types/index.ts` | Export `studentAcademics` |
| `packages/core/src/api/index.ts` | Export `academics.api` |
| `packages/core/src/students/index.ts` | Export `academics` helpers |
| `packages/core/src/query/queryKeys.ts` | `academicSummary`, `assessmentHistory`, `reportCards`, `reportCardDetail` |
| `packages/core/src/query/index.ts` | Export `useStudentAcademics` hooks |
| `packages/ui/src/student360/types.ts` | Tab id includes `academics` |
| `packages/ui/src/student360/index.ts` | Export academics UI |
| `apps/admin/.../StudentDetailScreen.tsx` | Academics tab + RBAC + navigation |
| `apps/admin/.../studentsStackTypes.ts` | `ReportCardDetail` route |
| `apps/admin/.../StudentsStackNavigator.tsx` | Register detail screen |
| `apps/admin/.../students/index.ts` | Export `ReportCardDetailScreen` |

**Backend:** None (Phase 0 APIs already deployed).

---

## 4. APIs reused (no new routes)

| Endpoint | Hook | UI |
| --- | --- | --- |
| `GET /students/{id}/academic-summary` | `useStudentAcademicSummary` | Overview KPIs, assessment count |
| `GET /students/{id}/assessment-history` | `useStudentAssessmentHistory` (infinite) | Timeline, filters, trend input |
| `GET /report-cards?student_id=` | `useStudentReportCards` | Report card history list |
| `GET /report-cards/{id}` | `useStudentReportCardDetail` | Position (latest) + detail screen |

---

## 5. Query architecture

```text
StudentDetailScreen (academics.view)
  └─ AcademicsTab (mounted only when tab active)
       useStudentAcademicSummary(studentId)
       useStudentAssessmentHistory(studentId, { category, subjectId })  ← useInfiniteQuery
       useStudentReportCards(studentId)
       useStudentReportCardDetail(latestReportCardId)  ← position only

ReportCardDetailScreen
  useStudentReportCardDetail(reportCardId)
```

**Query keys** (`queryKeys.students`):

| Key | Identity |
| --- | --- |
| `academicSummary(id, { termId, academicYearId })` | Summary scope |
| `assessmentHistory(id, { category, subjectId, termId, academicYearId })` | Filters |
| `reportCards(id)` | List per student |
| `reportCardDetail(id)` | Single card payload |

**Client normalization:** `normalizeAssessmentHistoryRow`, `normalizeAcademicSummary`, `mapToDisplayCategory`, `displayCategoryToApiTypes` in `@erp/core/students/academics`.

**Timeline display buckets:** CAT, Quiz, Assignment, Exam, Portfolio, Report Card (mapped from canonical API `type` codes).

---

## 6. Caching strategy

| Query | staleTime | Notes |
| --- | --- | --- |
| Academic summary | 60 s | Shared KPIs for overview card |
| Assessment history (per page) | 45 s | `useInfiniteQuery`; pages cached by `pageParam` |
| Report cards list | 60 s | Up to 50 cards per student |
| Report card detail | 120 s | Heavier payload; reused for latest position + detail screen |

**Invalidation:** None automatic on tab switch; Retry on error in tab. Filter/category change creates a new `assessmentHistory` cache key (expected refetch).

**Load more:** `fetchNextPage()` when `hasNextPage` (25 rows per page).

---

## 7. UI components (`@erp/ui/student360/academics`)

| Component | Role |
| --- | --- |
| `AcademicOverviewCard` | Average, grade, position, assessment count, trend delta |
| `PerformanceTrend` | Bar chart from term/report or recent scored events |
| `AssessmentFilters` | Horizontal category + subject chips |
| `AssessmentTimeline` | Icon/colour per display category |
| `ReportCardHistoryList` | Tappable rows → `ReportCardDetail` |

---

## 8. RBAC

| Permission | Behavior |
| --- | --- |
| `students.view` | Student 360 access (unchanged) |
| `academics.view` | Shows **Academics** tab between Attendance and Fees |
| Without `academics.view` | Tab hidden; no academic API calls from 360 |

Server-side student access rules unchanged (teacher scope, parent published report cards).

---

## 9. Risks and limitations

| Risk | Mitigation / note |
| --- | --- |
| **Report card index `overall_percentage: 0`** | List uses assessment-history `report_card_term` % when present; otherwise label “View detail” until `show` loads |
| **Position only on report card show** | Extra `useStudentReportCardDetail(latestId)` call; shows — if no published card |
| **In-memory history pagination (server)** | Large histories may be slow; “Load more” fetches next API page |
| **Subject filter options** | Built only from already-loaded history pages (may miss subjects on later pages) |
| **Trend with one term** | Falls back to last 8 scored non–report-card events |
| **No CBC block** | By design; no CBC read API in product |
| **No class-sheet position API** | Position not fetched from `/reports/exams/class-sheet` (avoid heavy class payload) |

---

## 10. Out of scope (confirmed)

- CBC competency UI  
- Assessment / mark entry  
- New Laravel endpoints  
- Pull-to-refresh on 360 shell  
- Homework / assignments module  

---

## 11. Suggested follow-ups

1. Pull-to-refresh on Academics tab invalidating summary + history + report cards.  
2. Term/year picker scoped to `current_term_id` from history `meta`.  
3. Deep-link report card from timeline `report_card:*` rows.  
4. Enrich `GET /students/{id}/stats` `exam_average` from `academic-summary` on Overview tab.
