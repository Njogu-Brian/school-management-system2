# Implementation Status — Design System V3

> Updated July 2026. Soft-3D + frosted nav + critical UX bugfixes (attendance loop, approvals crash, bio/OTP, persistent tabs).

## Tracks

| Track | Status | Visible? |
|-------|--------|----------|
| Stage 1–3 foundation / modules / polish | Done | Yes |
| Visual Overhaul (login, heroes, floating tabs) | Done | Yes |
| Motion a11y + branded feedback | Done | Yes |
| **Soft-3D icons + frosted nav** | Done | **Yes** |
| Critical UX bugfixes (Jul 21) | Done | **Yes** |

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

## Auth / biometrics

| Behavior | Status |
|----------|--------|
| Enable biometrics stores token + last password credentials | Done |
| Logout keeps biometric enrollment (unlock on next visit) | Done |
| Different user login clears bio binding and re-prompts enable | Done |
| Login screen biometric-first when enrolled | Done |
| OTP sign-in (`/login/otp/request` + `/verify`) | Done |

## Known mismatch — collections figures

Android Admin and Web may show different **collections / total invoiced** totals because they use different aggregations or date scopes (e.g. `GET /finance/summary` vs dashboard stats vs web finance reports; mobile fallbacks also sum only the first page of payments). Align term / academic year / campus filters when comparing. Tracked for a follow-up API parity pass.

**Mitigation (Jul 21):** Billing and Collections list screens now show a KPI strip from `useFinanceDashboardKpis` / `GET /finance/summary` (same source as the Finance dashboard), so list + hub figures stay aligned within the mobile app.

## How to verify

```bash
cd mobile-app
npm run test:design-system
```

Reload Expo Go (`r`). Prefer `npx.cmd expo start --offline` if online CLI validation fails.

## Deferred

- Custom display font
- Detox E2E
- Full finance / settings visual depth pass beyond Soft3D + tab clearance
- KPI sparklines / tablet master-detail
