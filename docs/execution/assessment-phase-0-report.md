# Assessment Engine Phase 0 — Implementation Report

**Date:** 2026-06-04  
**Scope:** Read-only Assessment Read Layer (no new tables, no schema changes, no report card generation changes).

---

## 1. Summary

Phase 0 introduces a **unified read facade** over existing academic tables so Student 360 and mobile clients can load assessment history and academic summaries **without** migrating data or touching `ReportCardBatchService`.

---

## 2. Files created

| File | Purpose |
| --- | --- |
| `app/DTO/Academics/AssessmentHistoryItem.php` | Normalized history row DTO with `toArray()` |
| `app/Services/Academics/AssessmentTypeResolver.php` | Maps legacy rows → canonical `type` codes |
| `app/Services/Academics/AssessmentReadFacade.php` | Aggregates, filters, paginates history; builds summary |
| `app/Http/Controllers/Api/ApiStudentAssessmentController.php` | Sanctum API endpoints + access checks |
| `tests/Unit/Academics/AssessmentTypeResolverTest.php` | Unit tests for type resolution |
| `tests/Feature/Api/StudentAssessmentReadTest.php` | API smoke tests (sqlite) |
| `docs/execution/assessment-phase-0-report.md` | This report |

---

## 3. Files modified

| File | Change |
| --- | --- |
| `routes/api.php` | Registered two student routes inside `auth:sanctum` group |

**Not modified (by design):**

- `app/Services/ReportCardBatchService.php`
- Any migration or model persistence logic
- Existing `/marks`, `/exams`, `/report-cards` endpoints

---

## 4. APIs added

### `GET /api/students/{student}/assessment-history`

| Item | Detail |
| --- | --- |
| **Auth** | `auth:sanctum` |
| **Access** | Same as `ApiReportCardController`: teacher scope, parent/guardian linked students |
| **Parent/Guardian** | Exam marks only when exam `publish_result` or status published/locked/approved; portfolios `assessed`/`published`; report cards with `published_at` |
| **Query** | `page`, `per_page` (max 100), `academic_year_id`, `term_id`, `subject_id`, `type` (comma-separated or array), `from`, `to` (dates) |
| **Response** | Paginated `data.data[]` of normalized items + `meta.student_id`, `meta.current_term_id` |

**Item shape (normalized):**

```json
{
  "id": "exam_mark:42",
  "type": "cat",
  "type_label": "CAT",
  "title": "CAT 1 Mathematics",
  "subject_id": 7,
  "subject_name": "Mathematics",
  "academic_year_id": 3,
  "term_id": 5,
  "assessed_on": "2026-05-14",
  "score_raw": 24,
  "score_max": 30,
  "score_display": "24/30",
  "score_percent": 80,
  "grade_label": "A",
  "performance_level": { "id": 2, "code": "M", "name": "Meeting Expectations" },
  "status": "published",
  "remark": null,
  "legacy_source": { "table": "exam_marks", "id": 42 }
}
```

### `GET /api/students/{student}/academic-summary`

| Item | Detail |
| --- | --- |
| **Auth** | `auth:sanctum` |
| **Query** | Optional `academic_year_id`, `term_id` (scopes aggregates) |
| **Response** | KPI object (no pagination) |

**Summary fields:**

- `exam_average` — mean of `score_percent` from exam marks, weekly assessments, portfolios (where percent exists)
- `latest_overall_percentage` / `latest_overall_grade` — from latest report card `summary` JSON (not `ReportCardBatchService::build`)
- `latest_performance_level` — from `report_cards.overall_performance_level_id`
- Counts: `marks_recorded_count`, `portfolio_count`, `weekly_assessment_count`, `report_cards_count`, `published_report_cards_count`
- `assessment_counts_by_type` — histogram keyed by canonical type
- `current_term_id` — `terms.is_current`

---

## 5. Data sources used

| Source table | History rows | Type resolution |
| --- | --- | --- |
| `exam_marks` (+ `exams`, `subjects`, `cbc_performance_levels`) | Per mark | `is_cat`, `assessment_method`, `exam_category`, exam type/name heuristics |
| `assessments` (weekly) | Per row | `assessment_type` string heuristics → `weekly_assessment`, `assignment`, etc. |
| `portfolio_assessments` | Per portfolio | `portfolio_type === project` → `project`, else `portfolio` |
| `report_cards` | Per term card | Fixed `report_card_term`; %/grade from `summary` JSON only |

**Composite IDs:** `{source}:{pk}` e.g. `exam_mark:12`, `report_card:3` — stable for UI keys in Phase 0.

---

## 6. Performance considerations

| Topic | Approach | Risk |
| --- | --- | --- |
| **Queries** | Up to four bounded queries (marks, weekly, portfolio, report cards) with eager loads | Large mark histories load all rows before pagination |
| **Pagination** | In-memory slice after merge/sort | Acceptable for Phase 0; Phase 1+ should paginate per source or materialize |
| **Sorting** | `assessed_on` descending | Null dates sort last |
| **Report cards** | Does **not** call `ReportCardBatchService::build` | Avoids N×heavy DTO builds |
| **Summary** | Reuses same collectors as history (scoped) | Duplicate work if client calls both endpoints; acceptable for v1 |
| **Indexes** | Relies on existing FK indexes (`student_id`, `exam_id`) | No new indexes (no schema change) |

**Recommendation for mobile:** Default `per_page=20`, pass `term_id` / `academic_year_id` when drilling into a term; cache summary for Overview tab.

---

## 7. Migration impact

| Area | Impact |
| --- | --- |
| **Database** | None — no migrations |
| **Legacy writes** | Unchanged — teachers still write `exam_marks` etc. |
| **Report cards** | Unchanged — generation still uses `ExamMark` directly |
| **Existing APIs** | Fully backward compatible; new routes only |
| **Phase 1 readiness** | `legacy_source` + canonical `type` align with future `assessment_results` backfill |

---

## 8. Type catalog (Phase 0)

| `type` | Source |
| --- | --- |
| `traditional_exam` | Default exam mark |
| `cat` | `exams.is_cat` |
| `oral` / `practical` / `portfolio` / `project` | `assessment_method` / portfolio type |
| `cbc_formative` / `cbc_summative` | `exams.exam_category` |
| `speed_test` | Exam name / exam type name heuristic |
| `weekly_assessment` | Weekly `assessments` default |
| `assignment` | Weekly `assessment_type` string |
| `report_card_term` | `report_cards` |

`speed_test` and homework-linked `assignment` scores are only present when legacy data exists; homework table has no per-student scores yet.

---

## 9. Testing

```bash
php artisan test --filter=AssessmentTypeResolverTest
php artisan test --filter=StudentAssessmentReadTest
```

Feature tests skip on `mysql` default connection (same pattern as other API tests in this repo).

---

## 10. Next steps (out of Phase 0 scope)

1. Wire Admin App Student 360 Academics tab to `assessment-history` + `academic-summary`.
2. Optionally enrich `GET /students/{id}/stats` `exam_average` from `academic-summary` (single line change, separate PR).
3. Phase 1: `assessment_events` / `assessment_results` tables + backfill from `legacy_source` keys.
