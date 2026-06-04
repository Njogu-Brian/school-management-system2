# Sprint 3 — Batch 2 Report: Student 360 Foundation

**Status:** Complete  
**Scope:** Student 360 layout with Overview, Attendance, Fees, and Family tabs. No CBC, Academics, Health, Discipline, Transport, or Documents.  
**Verification:** `tsc --noEmit` passes for `packages/core`, `packages/ui`, and `apps/admin`.

---

## 1. API audit

| Need | Endpoint | Notes |
| --- | --- | --- |
| Student profile | `GET /students/{id}` | Photo, name, class, guardians, parent block, emergency fields, `fee_status` |
| Attendance % | `GET /students/{id}/stats` | `attendance_percentage`, optional `fees_balance` (role-gated server-side) |
| Attendance calendar | `GET /students/{id}/attendance-calendar` | `year`, `month` → daily `status` (present/absent/late) |
| Fee balance / ledger | `GET /students/{id}/statement` | `closing_balance`, `total_invoiced`, `total_paid`, `transactions` (invoice/payment) |
| Parent info | Embedded in `GET /students/{id}` | `parent` object + `guardians[]` (no separate parent API) |

---

## 2. APIs reused

| API | Used in |
| --- | --- |
| `GET /students/{id}` | Header, Family tab, detail normalization |
| `GET /students/{id}/stats` | Overview attendance summary; fee balance when permitted |
| `GET /students/{id}/attendance-calendar` | Attendance tab + 3-month trend (parallel queries) |
| `GET /students/{id}/statement` | Overview fee summary, Fees tab, timeline transactions |

## 3. APIs created

**None.**

---

## 4. Query architecture

```text
StudentDetailScreen
  useStudentDetail(id)              — profile + family payload
  useStudentStats(id)               — attendance %, fees_balance
  useStudentStatement(id, year)     — enabled when finance.view
  useStudentAttendanceTrend(id)     — 3× calendar queries (current + 2 prior months)

Tab content
  OverviewTab   — summary widgets, parent blurb, buildStudentTimeline(statement)
  AttendanceTab — summarizeAttendanceDays + buildAttendanceTrend
  FeesTab       — statement split into invoices / payments
  FamilyTab     — parent, guardians, emergency (from detail)
```

**Query keys** (`queryKeys.students`):

- `detail(id)`
- `stats(id)`
- `statement(id, year)`
- `attendanceCalendar(id, year, month)`
- `attendanceTrend(id)` — implemented as three `attendanceCalendar` queries via `useQueries`

---

## 5. Caching strategy

| Query | staleTime | Notes |
| --- | --- | --- |
| Detail | 60 s | From Batch 1 `useStudentDetail` |
| Stats | 60 s | Shared across Overview + Attendance header |
| Statement | 60 s | Current calendar year default |
| Calendar months | 45 s | Three parallel month caches per student |

Refetch: pull-to-refresh not on 360 shell yet; per-tab Retry on errors. Tab switch does not invalidate; attendance trend loads when Overview or Attendance tab active.

---

## 6. RBAC

| Permission | Behavior |
| --- | --- |
| `students.view` | Required for registry/detail (existing area guard) |
| `finance.view` | Fees tab amounts + overview balance widget; without it, fee **status** only from header/detail |

Server may omit `fees_balance` in stats and `outstanding_balance` on student when role cannot view amounts.

---

## 7. UI architecture (`@erp/ui/student360`)

| Component | Role |
| --- | --- |
| `Student360Layout` | Header + sticky tab bar + tab body |
| `Student360Header` | Photo, name, admission #, class, enrollment + fee badges |
| `StudentSummaryWidgets` | 2-column KPI-style summary cells |
| `StudentTimeline` | Recent activity list (overview) |

Admin tabs: `OverviewTab`, `AttendanceTab`, `FeesTab`, `FamilyTab`.

---

## 8. Files created

### `@erp/core`

- `src/types/student360.ts`
- `src/students/family.ts`, `attendance.ts`, `timeline.ts`
- `src/query/hooks/useStudent360.ts`
- Extended `api/students.api.ts`, `types/student.ts`, `normalize.ts`

### `@erp/ui`

- `src/student360/*`

### `apps/admin`

- `src/features/students/student360/tabs/*`
- `src/features/students/student360/utils/formatters.ts`

### Docs

- `docs/execution/sprint-3-batch-2-report.md`

---

## 9. Files modified

- `packages/core/src/query/queryKeys.ts`, `query/index.ts`, `students/index.ts`, `types/index.ts`
- `packages/ui/src/index.ts`
- `apps/admin/src/features/students/screens/StudentDetailScreen.tsx`

---

## 10. Risks

| Risk | Mitigation |
| --- | --- |
| Attendance trend is client-derived from 3 calendar calls | Documented; not a dedicated analytics API |
| Gender/status filters on registry unchanged | Out of Batch 2 scope |
| Timeline is statement + admission only | Extend when activity/audit API exists |
| `stickyHeaderIndices` on ScrollView may vary by platform | Acceptable for MVP tab bar |
| Large statement lists truncated to 10 per section in Fees tab | Full ledger in future finance module |

---

## 11. Manual test checklist

1. Open student from registry → 360 header with photo, class, badges.
2. Overview → attendance %, fee/parent widgets, timeline.
3. Attendance → present/absent/late + weekly trend bars.
4. Fees (with `finance.view`) → balance, invoices, payments.
5. Family → father/mother/guardian/emergency rows from API payload.
