# Sprint 1 · Batch 1 — Admin App Shell — Build Report

> **Objective:** Stand up the Admin App **shell only** — monorepo structure, app entry, provider tree, navigation (drawer + bottom tabs), global header, theme, screen container, and placeholder screens for all 10 IA top-level areas. **No business features, no API, no mock data.**
> **Source of truth:** [`admin-app-build-plan.md`](./admin-app-build-plan.md) (§1–§7), Admin IA [`02-admin-information-architecture.md`](../admin-app/02-admin-information-architecture.md), Admin UI Specs [`06-ui-specifications.md`](../app-split/06-ui-specifications.md) (Part B).
> **Status:** ✅ Complete. TypeScript strict typecheck passes for `@erp/admin`, `@erp/core`, `@erp/ui` (exit 0).

---

## 1. What was built

A monorepo workspace was bootstrapped inside `mobile-app/` (its package name is already `school-erp-mobile`, the build plan's monorepo root). The existing app stays at the root untouched; the new Admin App and shared packages are **additive** workspace members.

| Deliverable | Where | Status |
|-------------|-------|--------|
| 1. `apps/admin` structure | `mobile-app/apps/admin/**` | ✅ |
| 2. App entry point | `apps/admin/index.js` + `App.tsx` | ✅ |
| 3. Provider tree | `apps/admin/App.tsx` | ✅ |
| 4. React Navigation setup | `apps/admin/src/navigation/AdminRootNavigator.tsx` | ✅ |
| 5. Drawer navigation | `DrawerNavigator.tsx` + `DrawerContent.tsx` | ✅ |
| 6. Bottom tabs | `BottomTabsNavigator.tsx` | ✅ |
| 7. Global header | `@erp/ui` `GlobalAppHeader.tsx` | ✅ |
| 8. Theme provider | `@erp/ui` `ThemeContext.tsx` + `tokens.ts` | ✅ |
| 9. Screen container | `@erp/ui` `ScreenContainer.tsx` | ✅ |
| 10. Placeholder screens | `apps/admin/src/features/*/screens/*` (×10) | ✅ |

**Routes created (all 10 IA areas):** Dashboard · Admissions · Students · Academics · Finance · People · Operations · Communication · Reports · Settings.

- **Bottom tabs (most-used preset, build plan §5.2):** Dashboard · Students · Finance · People.
- **Drawer (full module list, IA §1):** all 10 areas; tab areas deep-link into the tab host (`Workspace`), the other 6 are drawer stacks.

---

## 2. Files created

**Monorepo root config**
- `mobile-app/tsconfig.base.json` — shared strict TS config + `@erp/*` path aliases.

**`@erp/core` (shared brain — config slice only this batch)**
- `mobile-app/packages/core/package.json`
- `mobile-app/packages/core/tsconfig.json`
- `mobile-app/packages/core/src/index.ts`
- `mobile-app/packages/core/src/config/roles.ts` — `UserRole`, `ADMIN_APP_ROLES` (for future app-mismatch guard).
- `mobile-app/packages/core/src/config/navigation.ts` — `ADMIN_NAV_AREAS` (10 areas), `ADMIN_TAB_AREAS`, `getNavArea`.

**`@erp/ui` (design system — shell slice)**
- `mobile-app/packages/ui/package.json`
- `mobile-app/packages/ui/tsconfig.json`
- `mobile-app/packages/ui/src/index.ts`
- `theme/tokens.ts` — COLORS/SPACING/FONT_SIZES/BORDER_RADIUS/SHADOWS (ScholarCore brand).
- `theme/ThemeContext.tsx` — `ThemeProvider`, `useTheme`, resolved light/dark palette.
- `theme/index.ts`
- `layout/ScreenContainer.tsx` — safe-area + keyboard-aware wrapper.
- `layout/GlobalAppHeader.tsx` — persistent chrome (menu · title · branch · search · approvals · notifications · avatar), presentational.
- `layout/index.ts`
- `feedback/PlaceholderScreen.tsx` — module placeholder (identity + "coming soon" + planned sections).
- `feedback/AppErrorBoundary.tsx` — top-level render guard with local reset.
- `feedback/index.ts`

**`apps/admin` (shell)**
- `package.json` (`@erp/admin`), `app.config.ts` (bundle `com.schoolerp.admin`), `babel.config.js`, `metro.config.js` (monorepo-aware), `tsconfig.json`, `index.js`, `App.tsx`.
- `src/navigation/types.ts` — `TabsParamList`, `DrawerParamList`, typed `RootParamList`.
- `src/navigation/linking.ts` — deep-link config foundation.
- `src/navigation/DrawerContent.tsx` — custom drawer driven by `ADMIN_NAV_AREAS`.
- `src/navigation/BottomTabsNavigator.tsx`
- `src/navigation/DrawerNavigator.tsx`
- `src/navigation/AdminRootNavigator.tsx` — `NavigationContainer` + nav theme + linking.
- `src/features/<area>/screens/<Area>Screen.tsx` + `src/features/<area>/index.ts` for: dashboard, admissions, students, academics, finance, people, operations, communication, reports, settings (**20 files**).

**Docs**
- `docs/execution/batch-1-report.md` (this file).

> Total: **52 files created**.

## 3. Files modified

- `mobile-app/package.json` — added `"private": true` and `"workspaces": ["apps/*", "packages/*"]` to make it the monorepo root. No other change; the existing app's `main`/scripts/deps are untouched.

---

## 4. Architecture decisions

1. **Monorepo lives inside `mobile-app/` (additively).** The existing Expo app already is `school-erp-mobile` (the build plan's monorepo root). Rather than relocate it to `apps/staff` now (a large, risky move belonging to the Staff-refactor track), this batch only **adds** `apps/admin` and `packages/{core,ui}` as workspace members. The Staff app stays at the root and continues to build unchanged. Moving it under `apps/staff` is a later infra task.

2. **Shared packages are consumed as source (no build step).** `@erp/core` / `@erp/ui` expose `src/index.ts` as `main`/`types`; the admin app resolves them via TS path aliases (`tsconfig.base.json`) and Babel `module-resolver` (matching the repo's existing convention). A monorepo-aware `metro.config.js` watches the workspace root and resolves hoisted deps from the root `node_modules`.

3. **`@erp/ui` stays navigation-agnostic.** `GlobalAppHeader` is presentational and takes injected callbacks (`onMenuPress`, etc.). The navigation-aware `DrawerContent` lives in the app, not the design system — keeping the DS reusable and dependency-light. (The build plan listed `DrawerContent` under `ui/layout`; this is a deliberate refinement.)

4. **Drawer-hosts-tabs navigation model.** Root = Drawer. Its first screen `Workspace` hosts the bottom-tab navigator (Dashboard/Students/Finance/People); the remaining six areas are drawer screens. This satisfies "drawer is the full module list, tabs are shortcuts" (App Designs §5.7) while covering all 10 routes exactly once. Headers everywhere render the single `GlobalAppHeader`; the menu button dispatches `DrawerActions.openDrawer()` (bubbles correctly from nested tabs).

5. **Navigation tree is data, not code.** The 10 areas (labels, icons, descriptions, planned sections, `inTabs`) live in `@erp/core/config/navigation.ts`. This is the same data structure the permission-first `computeMenu` (build plan §7.3) will consume later — so menu gating drops in without restructuring.

6. **Provider tree trimmed to shell scope.** Implemented: `GestureHandlerRootView → ThemeProvider → SafeAreaProvider → AppErrorBoundary → AdminRootNavigator`. **Deliberately deferred** (require API/business logic, out of scope): `QueryClientProvider`, `AuthProvider`, `RbacProvider`, `ScopeProvider`, `NotificationPreferencesProvider`. Insertion points are documented in `App.tsx`.

7. **TypeScript strict everywhere.** `tsconfig.base.json` enables `strict`, `noUnusedLocals`, `noUnusedParameters`, `noImplicitOverride`, `noFallthroughCasesInSwitch`. All three projects typecheck at exit 0.

8. **No business logic / API / mock data.** Placeholders render only static module identity + planned-section copy (descriptive, not data). The branch label, search, bell, and approvals chrome are visual-only with no-op handlers.

---

## 5. Verification

| Check | Result |
|-------|--------|
| `tsc --noEmit` — `apps/admin` (strict) | ✅ exit 0 |
| `tsc --noEmit` — `packages/ui` (strict) | ✅ exit 0 |
| `tsc --noEmit` — `packages/core` (strict) | ✅ exit 0 |
| Editor lints (all new files) | ✅ none |
| Module resolution (RN/Expo/navigation/@erp/*) | ✅ resolves via root `node_modules` + path aliases |

**Runtime smoke test (navigation works / screens load):** not executed in this environment — see Blockers. To run:

```bash
# from mobile-app/ (monorepo root)
npm install                 # links @erp/admin, @erp/core, @erp/ui workspaces
cd apps/admin && npx expo start
```

Expected: app boots to Dashboard tab; bottom tabs switch between Dashboard/Students/Finance/People; the hamburger opens the drawer listing all 10 areas; selecting Admissions/Academics/Operations/Communication/Reports/Settings navigates to each placeholder; the global header (with branch chip + chrome icons) renders on every screen; light/dark follows the OS.

---

## 6. Blockers & follow-ups

**Blockers**
- **No runtime execution here.** This environment can't run Metro/Expo on a device/emulator, so "navigation works / screens load" is verified by **strict typecheck + correct, standard wiring** but not yet by an on-device click-through. A `npm install` at the monorepo root + `expo start` is the remaining confirmation step (commands above). *Risk: low — the structure follows the standard Expo monorepo + React Navigation v6 patterns and compiles cleanly.*
- **Workspace install pending.** Adding `workspaces` to the root `package.json` requires one `npm install` at `mobile-app/` to symlink the new packages before the admin app can start.

**Non-blocking follow-ups (later batches, intentionally out of scope)**
- Move the existing Staff app into `apps/staff` and finish the `@erp/core`/`@erp/ui` extraction (Roadmap Phase 1).
- Add Turborepo (`turbo.json`) + per-app EAS profiles, OTA channels, and a dedicated EAS project id for the Admin binary.
- Wire deferred providers: TanStack Query, Auth (+ explicit-deny `normalizeRole` + app-mismatch guard), RBAC engine (`computeMenu`), Scope (branch switcher), Notifications, Sentry/analytics.
- Promote remaining `@erp/ui` primitives (Button, Input, Card, StatTile, charts, `QueryBoundary`) from the Staff app during Phase 3 hardening.
- Replace placeholder screens module-by-module per the Sprint 2–6 plan (Command Center → Student Success → Settings → Finance → CBC).
