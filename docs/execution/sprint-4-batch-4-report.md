# Sprint 4 — Batch 4 Report: Staff 360 MVP

**Status:** Complete  
**Scope:** Read-only Staff 360 with Overview, Employment, Leave, and Attendance tabs. Two smallest backend reads from Batch 3 audit. No create/edit, payroll tab, performance, or training.  
**Verification:** `tsc --noEmit` passes for `packages/core`, `packages/ui`, and `apps/admin`. PHP lint passes on `ApiStaffController`.

---

## 1. APIs reused

| Method | Route | Staff 360 use |
| --- | --- | --- |
| GET | `/api/staff/{id}` | Header, Overview quick profile, Employment tab |
| GET | `/api/leave-requests?staff_id=` | Leave history list (Leave tab + pending count on Overview) |
| GET | `/api/payroll-records?staff_id=` | Latest net pay widget on Overview (`finance.view` only) |

**Not used in this batch (still available):**

| Method | Route | Reason |
| --- | --- | --- |
| GET | `/api/staff-attendance/staff/history` | Superseded by new `attendance-history` (Secretary + date range + summary) |
| GET | `/api/leave-types` | Leave type names already embedded in leave-requests / balances payloads |

---

## 2. APIs added

| Method | Route | Controller | Purpose |
| --- | --- | --- | --- |
| GET | `/api/staff/{id}/leave-balances` | `ApiStaffController@leaveBalances` | Active-year `staff_leave_balances` per type |
| GET | `/api/staff/{id}/attendance-history` | `ApiStaffController@attendanceHistory` | Date-range history + summary + pagination |

### 2.1 `GET /staff/{id}/leave-balances`

**Permissions:** `assertCanViewStaffRecord` — Super Admin, Admin, Secretary, self, senior teacher (supervised).

**Query params:** None (active academic year implied).

**Response shape:**

```json
{
  "success": true,
  "data": {
    "staff_id": 42,
    "academic_year": { "id": 1, "name": "2026" },
    "balances": [
      {
        "id": 10,
        "leave_type_id": 3,
        "leave_type_name": "Annual Leave",
        "leave_type_code": "annual",
        "academic_year_id": 1,
        "entitlement_days": 21,
        "used_days": 5,
        "remaining_days": 16,
        "carried_forward": 0
      }
    ]
  }
}
```

### 2.2 `GET /staff/{id}/attendance-history`

**Permissions:** Same as staff detail (`assertCanViewStaffRecord`) — fixes Secretary / admin-without-staff-profile gaps on legacy `staff-attendance/staff/history`.

**Query params:**

| Param | Default | Notes |
| --- | --- | --- |
| `start_date` | First day of current month | ISO date |
| `end_date` | Last day of current month | ISO date |
| `per_page` | 30 | Max 100 |
| `page` | 1 | Pagination |

**Response shape:**

```json
{
  "success": true,
  "data": {
    "staff": { "id": 42, "full_name": "Jane Doe" },
    "start_date": "2026-06-01",
    "end_date": "2026-06-30",
    "summary": { "total": 20, "present": 18, "absent": 1, "late": 1, "half_day": 0 },
    "history": {
      "data": [
        {
          "id": 101,
          "staff_id": 42,
          "date": "2026-06-03",
          "status": "present",
          "check_in_time": "07:58",
          "check_out_time": "16:02",
          "source": "clock",
          "marked_by": 5,
          "notes": null
        }
      ],
      "current_page": 1,
      "last_page": 1,
      "per_page": 30,
      "total": 20
    }
  }
}
```

**Files:**

- `app/Http/Controllers/Api/ApiStaffController.php` — `leaveBalances`, `attendanceHistory`, formatters
- `routes/api.php` — routes registered before `GET /staff/{id}`

---

## 3. Query architecture

### 3.1 Query keys (`queryKeys.staff`)

| Key | Endpoint |
| --- | --- |
| `detail(id)` | `GET /staff/{id}` |
| `leaveBalances(id)` | `GET /staff/{id}/leave-balances` |
| `leaveRequests(id, status?)` | `GET /leave-requests?staff_id=` |
| `attendanceHistory(id, range)` | `GET /staff/{id}/attendance-history` |
| `payrollRecords(id)` | `GET /payroll-records?staff_id=` |

### 3.2 Hooks (`@erp/core/query/hooks/useStaff360.ts`)

| Hook | Returns |
| --- | --- |
| `useStaffLeaveBalances` | Normalized `StaffLeaveBalanceItem[]` |
| `useStaffLeaveRequests` | Paginated `LeaveRequestRecord` envelope |
| `useStaffAttendanceHistory` | `days`, `summary`, `range`, pagination meta |
| `useStaffPayrollRecords` | Paginated payroll rows |
| `useStaffLatestPayroll` | First payslip as `StaffPayrollSummary` |

### 3.3 Tab-driven fetching (`StaffDetailScreen`)

Queries load only when their tab (or Overview aggregate) is active:

| Tab | Queries enabled |
| --- | --- |
| Overview | `detail`, `leaveBalances`, `attendanceHistory`, `leaveRequests(pending)`, `latestPayroll`* |
| Employment | `detail` only |
| Leave | `detail`, `leaveBalances`, `leaveRequests` |
| Attendance | `detail`, `attendanceHistory` |

\* `latestPayroll` only when `useCan('finance.view')`.

### 3.4 Normalization (`@erp/core/staff/staff360.ts`)

- `toLeaveBalanceItem`, `toAttendanceDay`, `toPayrollSummary`
- `summarizeStaffAttendance` — present rate from server summary
- `totalLeaveRemaining` — Overview widget

---

## 4. Caching strategy

| Query | `staleTime` | Rationale |
| --- | --- | --- |
| Staff detail | 60s | Stable profile; matches Batch 2 |
| Leave balances | 60s | Changes on approval (invalidate from approvals flow later) |
| Leave requests | 45s | More volatile around approvals |
| Attendance history | 45s | Daily marks; month-scoped key includes `startDate`/`endDate` |
| Payroll records | 60s | Monthly cadence |

**Cache identity:** Attendance keyed by `{ staffId, startDate, endDate, page }` so changing month range (future UI) won't collide.

**No prefetch:** Tabs fetch on first visit to reduce idle API load from registry browsing.

---

## 5. UI architecture

### 5.1 Shared shell (`@erp/ui/staff360`)

| Component | Role |
| --- | --- |
| `Staff360Layout` | Header + horizontal tab bar + back navigation |
| `Staff360Header` | Avatar, name, org line, employment badge, role |
| `StaffFieldSection` | Grouped read-only field cards |
| `LeaveBalanceCards` | Balance grid |
| `LeaveRequestListItem` | Leave history row |
| `AttendanceDayListItem` | Daily attendance row |

Reuses `StudentSummaryWidgets` for Overview/Attendance metric grids (same visual language as Student 360).

### 5.2 Admin tabs (`apps/admin/.../staff360/tabs`)

| Tab | Content |
| --- | --- |
| **Overview** | Summary widgets (employment, leave remaining, attendance %, optional latest payroll), quick profile fields |
| **Employment** | Position, contract, identity, emergency contact; payroll/statutory section gated by `finance.view` |
| **Leave** | Balance cards + leave request history |
| **Attendance** | Month summary widgets + daily log (clock vs manual label) |

### 5.3 RBAC

| Gate | Permission |
| --- | --- |
| Screen access | `people.view` **or** `staff.view` |
| Payroll widgets + banking section | `finance.view` |
| Server enforcement | `assertCanViewStaffRecord`, `assertPayrollApiAccess` |

---

## 6. Risks

| Risk | Impact | Mitigation in Batch 4 |
| --- | --- | --- |
| Leave balances empty for staff without HR setup | Overview/Leave show “no balances” | Empty state copy; mirrors web |
| Attendance month defaults to calendar month | May not match school pay period | Documented; `start_date`/`end_date` params ready for date picker |
| Payroll on Overview only | No payslip PDF or full payroll tab | Scoped to widget; full tab deferred |
| `basic_salary` vs latest payroll net | Possible mismatch on Overview | Widget labels “Latest net pay” vs “Configured basic salary” on Employment |
| Sensitive bank fields on Employment | Data exposure | Hidden without `finance.view` |
| Leave approval invalidation | Stale balances after approve from inbox | Future: invalidate `leaveBalances` + `leaveRequests` on approval mutation |
| Pagination on attendance | Only first page loaded in MVP | `per_page=30` sufficient for month view; load-more deferred |

---

## 7. File inventory

**Backend**

- `app/Http/Controllers/Api/ApiStaffController.php`
- `routes/api.php`

**`@erp/core`**

- `types/staff360.ts`, `types/staff.ts` (extended `StaffDetail`)
- `api/staff.api.ts`, `api/payroll.api.ts`
- `staff/staff360.ts`
- `query/hooks/useStaff360.ts`, `query/queryKeys.ts`

**`@erp/ui`**

- `staff360/*` (layout, header, field section, list items)

**`apps/admin`**

- `features/people/screens/StaffDetailScreen.tsx` (Staff 360 shell)
- `features/people/staff360/tabs/*`
- `features/people/staff360/utils/formatters.ts`

---

## 8. Out of scope (unchanged)

- Staff create / edit / photo upload
- Payroll tab, payslip PDF, advances
- Performance and training modules
- Documents tab
- Attendance date-range picker UI (API supports it)

---

*End of Sprint 4 Batch 4 report.*
