# Sprint 3 — Batch 1 Report: Student Registry Foundation

**Status:** Complete  
**Scope:** Student list, search, filters, detail routing shell. No Student 360 tabs (Family, Fees, Academics).  
**Verification:** `tsc --noEmit` passes for `packages/core`, `packages/ui`, and `apps/admin`.

---

## 1. API audit (Laravel)

| Capability | Endpoint | Query / notes |
| --- | --- | --- |
| Student listing | `GET /api/students` | Paginated `data`, `total`, `current_page`, `last_page` |
| Student detail | `GET /api/students/{id}` | Full `formatStudent` payload |
| Search | Same list | `search` (name, admission number); alias `name` exists |
| Class filter | Same list | `classroom_id` or `class_id` |
| Stream filter | Same list | `stream_id` |
| Class options | `GET /api/classes` | `id`, `name`, `level` (used as grade) |
| Stream options | `GET /api/classes/{id}/streams` | Per-class streams |

**Not supported server-side on list:** `gender`, enrollment/fee status, grade level alone. Those are applied **client-side** after fetch (and grade narrows the class picker via `level`).

**List scope:** API returns non-archived, non-alumni students only (`archive = 0`, `is_alumni = false`).

---

## 2. APIs reused

- `GET /students`
- `GET /students/{id}`
- `GET /classes`
- `GET /classes/{classId}/streams`

## 3. APIs created

**None.**

---

## 4. Query architecture

```text
StudentRegistryScreen
  useStudentRegistryState()     — debounced search + filter state
  useClassrooms()               — filter options (grade/class)
  useClassroomStreams(classId)  — stream chips
  useInfiniteStudentList(filters)
        → fetchStudentListPage()
        → studentsApi.list({ search, class_id, stream_id, page })
        → toStudentSummary() + applyStudentClientFilters()

StudentDetailScreen
  useStudentDetail(studentId)   — GET /students/{id} + grade level from classrooms cache
```

**Query keys** (`queryKeys.students`):

- `list(filters)` — infinite list
- `detail(id)` — profile shell
- `classrooms()` — class/grade metadata (5 min stale)
- `streams(classId)` — stream metadata

---

## 5. Caching strategy

| Query | staleTime | Notes |
| --- | --- | --- |
| Classrooms | 5 min | Rarely changes; shared across list pages |
| Streams | 5 min | Keyed by `classId` |
| Student list | 45 s | `useInfiniteQuery`; pages keyed by full filter object |
| Student detail | 60 s | Refetch on focus per QueryClient defaults |

Pull-to-refresh calls `refetch()` on the infinite query. Pagination via `fetchNextPage` when `hasMore`.

---

## 6. Models

| Model | Location | Purpose |
| --- | --- | --- |
| `StudentRecord` | `@erp/core` | Raw API shape |
| `StudentSummary` | `@erp/core` | Registry list row |
| `StudentDetail` | `@erp/core` | Detail header + profile fields |
| `StudentListFilters` | `@erp/core` | Query + client filter state |

---

## 7. UI components (`@erp/ui/students`)

- `StudentSearchBar`
- `StudentFilters` (grade, class, stream, status, gender)
- `StudentListItem`
- `StudentStatusBadge` (enrollment + fee)

---

## 8. Navigation

`StudentsStackNavigator` on the Students tab:

- `StudentRegistry` — default list
- `StudentDetail` — `{ studentId, summary? }` for instant header while detail loads

---

## 9. Files created

### `@erp/core`

- `src/types/student.ts`
- `src/api/students.api.ts`
- `src/students/normalize.ts`, `fetchStudents.ts`, `index.ts`
- `src/query/hooks/useStudentList.ts`, `useStudentDetail.ts`

### `@erp/ui`

- `src/students/*` (search, filters, list item, status badge)

### `apps/admin`

- `src/features/students/screens/StudentRegistryScreen.tsx`
- `src/features/students/screens/StudentDetailScreen.tsx`
- `src/features/students/hooks/useStudentRegistryState.ts`
- `src/features/students/utils/mapToListItem.ts`
- `src/features/students/models/index.ts`
- `src/navigation/StudentsStackNavigator.tsx`, `studentsStackTypes.ts`

### Docs

- `docs/execution/sprint-3-batch-1-report.md`

---

## 10. Files modified

- `packages/core` — exports, `queryKeys.students`
- `packages/ui/src/index.ts`
- `apps/admin/src/navigation/BottomTabsNavigator.tsx`
- `apps/admin/src/features/students/index.ts`
- Removed `StudentsScreen.tsx` placeholder

---

## 11. Risks

| Risk | Mitigation |
| --- | --- |
| Gender / fee status filters only client-side | Documented; may thin pages when combined with server class filter |
| Grade-only filter without class | Client-filters current page by `classroom.level`; select class for accurate server scope |
| Archived / alumni students not listable | API index excludes them; full archive UX needs future endpoint or param |
| Fee balance hidden if role lacks permission | `fee_status` still returned; amounts may be null |
| `useInfiniteStudentList` waits for classrooms query | Brief delay before first fetch; avoids missing grade level on rows |

---

## 12. Manual test checklist

1. Admin with `students.view` → Students tab → registry loads.
2. Search by name / admission number (debounced).
3. Filter by grade → class list narrows; pick class → list refetches.
4. Stream chips when class has streams.
5. Status: fees pending / cleared (client filter).
6. Gender filter (client).
7. Tap row → detail shell; pull to refresh on list.
