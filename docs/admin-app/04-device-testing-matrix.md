# 04 — Device Testing Matrix (Post–Sprint 10)

> **Purpose:** Validate Sprint 10 UX changes on real devices **before** starting Sprint 11.  
> **Type:** Testing only — no development.  
> **When:** Immediately after Sprint 10 merge; block Sprint 11 kickoff until P0 paths pass.

---

## Why this sprint exists

Sprint 10 changed cross-cutting surfaces:

| Change | Risk |
|--------|------|
| Filter bottom sheets | Apply/Clear state bugs, count badge wrong |
| Sticky search | Search hidden behind keyboard, list jump on refresh |
| Skeleton loading | Empty flash, double-fetch flicker |
| Global search grouping | Wrong module headers, offline cache stale |
| Collapsing 360 headers | Compact bar overlap, tab stickiness broken |
| Settings card layout | Section selection, modal flows |
| KPI trend deltas | Misleading ↑/↓ when chart data sparse |

These are exactly the kinds of changes that **look fine in typecheck** but fail on device.

---

## Test environment

| Item | Requirement |
|------|-------------|
| Build | Latest `main` after Sprint 10 commit |
| Backend | Staging or production-like tenant with ~300+ students |
| Accounts | One test user per role below (or Super Admin impersonation if available) |
| Devices | Minimum 2: one iOS, one Android |
| Network | Wi‑Fi + one run on degraded mobile data |
| Offline | One run with airplane mode toggled mid-session |

### Recommended devices

| Platform | Form factor | Notes |
|----------|-------------|-------|
| Android | Phone (6.1"–6.7") | Primary school admin device in Kenya |
| Android | Small phone (≤5.8") | Filter sheet + sticky search stress test |
| iOS | iPhone | Safe area, header collapse |
| Tablet (optional) | iPad / Android tab | Drawer + grid layouts |

---

## Cross-cutting regression (all roles)

Run once per device **before** role journeys.

| # | Area | Steps | Pass |
|---|------|-------|------|
| R1 | **Global search prompt** | Tap “Search anything…” in top bar → Global Search opens | ☐ |
| R2 | **Global search sticky** | Type query → scroll results → search bar stays visible | ☐ |
| R3 | **Global search groups** | Search student name → results grouped under Students / Staff / Finance | ☐ |
| R4 | **Filter sheet** | Open Students → Filters (n) → change grade → Apply → list updates | ☐ |
| R5 | **Filter clear** | Open sheet → Clear → badge shows 0, list resets | ☐ |
| R6 | **Sticky search** | Students → scroll past hero → search bar remains pinned | ☐ |
| R7 | **Skeleton → content** | Pull-to-refresh on Staff → skeleton rows, then list (no spinner flash) | ☐ |
| R8 | **360 collapse** | Student 360 → scroll → large header fades, compact name bar appears, tabs stick | ☐ |
| R9 | **Settings cards** | Settings → tap Academic card → content loads; Session & About modals open | ☐ |
| R10 | **KPI trends** | Dashboard → collections/enrollment widgets show ↑/↓ or fallback caption | ☐ |
| R11 | **Biometric re-login** | Kill app → reopen → biometric or session restore | ☐ |
| R12 | **Push tap-through** | Tap notification → lands on correct detail screen | ☐ |

**Tester:** _______________ **Date:** _______________ **Device:** _______________

---

## Role-based journeys

Legend: **P0** = must pass to ship · **P1** = should pass · **—** = not in role scope

### Director

| # | Journey | P | Steps | Pass | Notes |
|---|---------|---|-------|------|-------|
| D1 | Login | P0 | Google sign-in or email → lands on Dashboard | ☐ | |
| D2 | Dashboard | P0 | KPI widgets load; period picker works; no layout overflow | ☐ | |
| D3 | Global search | P0 | Find student + invoice from search prompt | ☐ | |
| D4 | Finance overview | P0 | Finance tab → dashboard hero + summary chart | ☐ | |
| D5 | Billing | P0 | Billing list → sticky search → filter sheet → open invoice | ☐ | |
| D6 | Collections | P1 | Collections list → search receipt/student | ☐ | |
| D7 | Approvals | P0 | Drawer → Approvals → filter sheet → open item | ☐ | |
| D8 | Students registry | P0 | Students → hero stats → filter sheet → open Student 360 | ☐ | |
| D9 | Student 360 | P0 | All tabs load (Overview, Finance, Attendance, …) | ☐ | |
| D10 | Reports | P1 | Reports hub opens; weekly/expense lists load or show empty state | ☐ | |
| D11 | Notifications | P1 | Bell → list → detail → mark read | ☐ | |

---

### Principal

| # | Journey | P | Steps | Pass | Notes |
|---|---------|---|-------|------|-------|
| P1 | Login | P0 | Role lands on Dashboard with academics KPIs visible | ☐ | |
| P2 | Dashboard | P0 | Attendance + enrollment KPIs; executive section if permitted | ☐ | |
| P3 | Academics workspace | P0 | Academics tab → dashboard + exams funnel | ☐ | |
| P4 | Exams list | P0 | Exams → sticky search → filter sheet (status, year, term) | ☐ | |
| P5 | Exam detail | P1 | Open exam → read-only detail loads | ☐ | |
| P6 | Marks matrix | P1 | Marks matrix → empty/loading states correct | ☐ | |
| P7 | Approvals | P0 | Pending leave / lesson plans → approve or view detail | ☐ | |
| P8 | Students | P0 | Registry + 360; collapsing header on scroll | ☐ | |
| P9 | Staff directory | P0 | People → sticky search → filter sheet → Staff 360 | ☐ | |
| P10 | Staff 360 | P1 | Tabs load; compact header on scroll | ☐ | |

---

### Secretary

| # | Journey | P | Steps | Pass | Notes |
|---|---------|---|-------|------|-------|
| S1 | Login | P0 | Secretary role → correct drawer items only | ☐ | |
| S2 | Admissions | P0 | Pipeline KPIs → funnel chart → applications list | ☐ | |
| S3 | Admissions filters | P0 | Filter sheet (status) + sticky search on applications | ☐ | |
| S4 | Application 360 | P0 | Open application → tabs; collapsing header | ☐ | |
| S5 | Students enroll | P0 | Students registry → search → open profile | ☐ | |
| S6 | Finance billing | P1 | Invoice search + status filter sheet | ☐ | |
| S7 | Communication | P1 | Communication workspace → SMS history / announcements (if permitted) | ☐ | |
| S8 | New admission flow | P1 | Create/edit application if mobile supports; else web parity note | ☐ | |

---

### Senior Teacher

| # | Journey | P | Steps | Pass | Notes |
|---|---------|---|-------|------|-------|
| T1 | Login | P0 | Teacher/senior teacher → limited nav per RBAC | ☐ | |
| T2 | Academics | P0 | Exams list accessible; marks matrix if permitted | ☐ | |
| T3 | Moderation | P1 | Approvals inbox → lesson plan items (if in scope) | ☐ | |
| T4 | Students | P0 | Class-scoped student list/search | ☐ | |
| T5 | Student 360 | P1 | Academic tab + attendance tab read-only | ☐ | |
| T6 | Attendance | P1 | Class attendance API screen if exposed in admin app | ☐ | |
| T7 | Transport (teacher) | P2 | Teacher transport screen if assigned routes | ☐ | |

---

### Accountant

| # | Journey | P | Steps | Pass | Notes |
|---|---------|---|-------|------|-------|
| A1 | Login | P0 | Finance permissions only where expected | ☐ | |
| A2 | Finance dashboard | P0 | Hero + collections chart (KES bars, not misleading %) | ☐ | |
| A3 | Billing | P0 | Full filter + search regression on invoices | ☐ | |
| A4 | Collections | P0 | Payment list + search; skeleton on load | ☐ | |
| A5 | Reconciliation | P0 | Queue filter sheet (pending/confirmed/rejected) | ☐ | |
| A6 | Transaction detail | P1 | Open bank/C2B transaction read-only | ☐ | |
| A7 | Statements | P2 | Student statement search if in scope | ☐ | |
| A8 | No RBAC leak | P0 | Cannot open Settings / Academics without permission | ☐ | |

---

## Sprint 10 surface map (quick reference)

| Module | Sticky search | Filter sheet | Skeleton | 360 collapse |
|--------|---------------|--------------|----------|--------------|
| Students | ✅ | ✅ | ✅ | ✅ |
| Staff | ✅ | ✅ | ✅ | ✅ |
| Admissions | ✅ | ✅ | ✅ | ✅ |
| Finance (Billing) | ✅ | ✅ | ✅ | — |
| Finance (Collections) | ✅ | — | ✅ | — |
| Finance (Reconciliation) | ✅ | ✅ | ✅ | — |
| Academics (Exams) | ✅ | ✅ | ✅ | — |
| Approvals | ✅ | ✅ | ✅ | — |
| Dashboard | — | — | ✅ widgets | — |
| Settings | — | — | partial | — |
| Global Search | ✅ | module chips | ✅ | — |

---

## Defect log template

| ID | Role | Device | Severity | Steps | Expected | Actual | Status |
|----|------|--------|----------|-------|----------|--------|--------|
| BUG-001 | | | P0/P1/P2 | | | | Open |

**Severity:**

- **P0** — Blocks role journey or causes data loss
- **P1** — UX broken but workaround exists
- **P2** — Cosmetic / nice-to-fix

---

## Exit criteria (gate for Sprint 11)

| Gate | Criteria |
|------|----------|
| **G1** | All R1–R12 cross-cutting tests pass on iOS + Android |
| **G2** | All P0 role journeys pass for Director, Principal, Secretary, Accountant |
| **G3** | Zero open P0 defects |
| **G4** | P1 defects documented with owner; ≤ 3 open P1s |
| **G5** | Product sign-off on known gaps (Reports partial, Operations placeholder, Communication partial) |

---

## Known gaps (do not fail testing — document instead)

These are **expected** until future sprints:

| Area | Current state |
|------|----------------|
| Operations workspace | Partial (trips exist; inventory/visitors mostly placeholder) |
| Communication workspace | Screens exist; not full broadcast/WhatsApp parity |
| Reports | Hub + weekly/expense; not executive board pack |
| CBC / Accounting | Not started |
| Attendance (admin capture) | May be web-only or teacher app |

---

## Sign-off

| Role | Name | Date | Approved |
|------|------|------|----------|
| Product | | | ☐ |
| Engineering | | | ☐ |
| QA / Pilot school | | | ☐ |
