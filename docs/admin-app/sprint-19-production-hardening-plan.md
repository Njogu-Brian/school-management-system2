# Sprint 19 — Production Hardening Plan

Execution plan for production readiness feedback (June 2026).

## Phase 1 — Login, branding, copy, visibility (commit 1)
- [x] Login: wait for `/app-branding`; show logo, school name, portal colors, login background
- [x] Biometrics: label "Enable Biometrics" / "Use Biometrics" (not Face ID-specific)
- [x] Remove dev copy ("Real-time KPIs", API path hints on health tab)
- [x] KPI: "Enrollment" → "School population"; admissions subline from API
- [x] Global header: fix search bar layout; tap opens search with keyboard focus
- [x] Teaching tab: fix dark-on-dark text (`palette` tokens)
- [x] Operational status: live counts from `GET /operations/summary`

## Phase 2 — Finance & dashboard accuracy (commit 2)
- [x] Align outstanding fees with web (`GET /finance/summary` all-invoice balance)
- [x] Collections KPI: this week / this month / this term in one card
- [x] Executive analytics: outstanding uses same invoice query as finance
- [x] Finance hub: remove Reconciliation tab; arrears & pending txn KPIs tappable
- [x] Collections: Payments + Transactions sub-tabs with web filters

## Phase 3 — Search & alerts (commit 3)
- [x] Global search: students, staff, parents, vehicles, menus
- [x] Student registry: fuzzy multi-token search (tallia/talia)
- [x] Backend: `GET /search` + student list Levenshtein/similarity
- [x] Alerts tab: system logs + red alerts + push on critical
- [x] Notifications icon → notifications list (verify navigation)

## Phase 4 — Edits & student UX (commit 4)
- [x] Staff profile edit (not read-only)
- [x] Student profile edit
- [x] Student academics chart container overflow fix
- [x] Open invoice from finance / student finance tab

## Phase 5 — Missing modules from legacy app (commits 5–8)
- [x] Mark attendance (class picker + bulk mark)
- [x] Transport management (trips, vehicles, assignments)
- [x] Leave: admin request + approve queue (extend existing)
- [x] Payroll records (read + payslip)
- [x] Settings hub: school, academic years, theme dark/light toggle
- [x] Staff clock in/out + admin view records + geofence management

## Git commit strategy
One commit per phase when tests pass (`npm run typecheck`, `php -l`).
