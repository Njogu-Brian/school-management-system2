# Implementation Status — Design System V3

> Updated July 22, 2026. Soft-3D + frosted nav + operations features + UX hardening (dialogs, tab clearance, People hub, attendance submit).

## Tracks

| Track | Status | Visible? |
|-------|--------|----------|
| Stage 1–3 foundation / modules / polish | Done | Yes |
| Visual Overhaul (login, heroes, floating tabs) | Done | Yes |
| Motion a11y + branded feedback | Done | Yes |
| **Soft-3D icons + frosted nav** | Done | **Yes** |
| Critical UX bugfixes (Jul 21) | Done | **Yes** |
| **Operations + UX hardening (Jul 22)** | Done | **Yes** |

## Soft-3D + nav chrome checklist

| Surface | Status |
|---------|--------|
| `Soft3DIcon` free-standing soft-3D glyphs | Done |
| Floating frosted `PremiumTabBar` (BlurView on iOS + Android) | Done |
| Drawer tabs on drawer-only routes via `withWorkspaceTabBar` | Done |
| Compact drawer (~280dp / 72%) + real blur (not solid white) | Done |
| Global “Search anything” on **Dashboard only** | Done |
| Soft-3D header approvals / notifications icons | Done |
| Distinct Soft3D: Mark attendance = clipboard; Approvals = check | Done |
| `useFloatingTabBarClearance()` for lists / FABs / footers | Done |
| Dialogs above scrim (bright card, tappable actions) | Done |

## Auth / biometrics

| Behavior | Status |
|----------|--------|
| Enable biometrics stores token + last password credentials | Done |
| Logout keeps biometric enrollment (unlock on next visit) | Done |
| Different user login clears bio binding and re-prompts enable | Done |
| Login screen biometric-first when enrolled | Done |
| OTP sign-in (`/login/otp/request` + `/verify`) | Done |

## Jul 22 — product + UX hardening

| Area | What shipped |
|------|----------------|
| Dashboard | Combined population/attendance KPI; Approvals header centering; quick actions after KPIs |
| People / HR | **PeopleHub** as HR tab home; Leave management/types; Staff advances; Payroll records; Staff clock |
| Approvals | Staff advances in unified inbox; reject/approve wired; admission reject via API |
| Admissions | Waitlist / reject / enroll (class + invoice) on app + portal |
| Transport | Teacher filters; route students; permanent / short-term assign |
| Communications | Stronger compose; template requires system recipients; login announcements; in-app staff notify on publish |
| Concerns | New module (API + portal + Operations screens) |
| Finance | Share payment link (invoice, student fees, parent portal) |
| Layout | Floating tab bar must not cover last content; sticky footers / FAB use clearance hook |
| Dialogs | Scrim no longer covers card; brighter dialog surface in dark mode |
| Attendance | Sticky **Submit attendance**; clears local draft banner after successful push/queue |
| Geofence | Client validation before save; Secretary can manage |
| API client | Network failures log as `warn` (no red LogBox overlay) |

## Known mismatch — collections figures

Android Admin and Web may show different **collections / total invoiced** totals because they use different aggregations or date scopes (e.g. `GET /finance/summary` vs dashboard stats vs web finance reports; mobile fallbacks also sum only the first page of payments). Align term / academic year / campus filters when comparing. Tracked for a follow-up API parity pass.

**Mitigation (Jul 21):** Billing and Collections list screens now show a KPI strip from `useFinanceDashboardKpis` / `GET /finance/summary` (same source as the Finance dashboard), so list + hub figures stay aligned within the mobile app.

## How to verify

```bash
cd mobile-app
npm run test:design-system
```

Reload Expo Go (`r`). Prefer `npx.cmd expo start --offline` if online CLI validation fails.

**Smoke after Jul 22 deploy**

1. Confirm / Reject dialogs are bright and buttons respond  
2. HR tab → People hub → Leave / Payroll / Clock deep links  
3. Approvals → salary advance reject/approve  
4. Mark attendance → Submit clears draft banner  
5. Settings → Save geofence with valid lat/lng/radius  

## Deferred

- Custom display font
- Detox E2E
- Full finance / settings visual depth pass beyond Soft3D + tab clearance
- KPI sparklines / tablet master-detail
- Full payroll period create/run UI (list + staff 360 payslips exist; period workflow remains portal-first)
