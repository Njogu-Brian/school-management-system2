# Sprint 1 — Batch 3 Report: Permission Foundation

**Status:** Complete  
**Scope:** Permission engine, role presets, navigation filtering, route guards, dashboard visibility rules. No business features or dashboard widgets.  
**Verification:** `tsc --noEmit` passes for `packages/core` and `apps/admin` (strict mode).

---

## 1. Objective

Build the **permission foundation** for the Admin App on top of Batches 1–2.1:

- Permission + role models and registry  
- Imperative checks: `can`, `hasRole`, `hasAnyRole`, `hasAllRoles`  
- Hooks: `useCan`, `useRole`, `usePermissions`  
- Components: `PermissionGate`, `RoleGate`, `Can`  
- Permission-first drawer + tab menus  
- Dashboard tab visibility rules (no widgets)  
- Route-level protection → module Access Denied  

---

## 2. Architecture

```text
AuthProvider (user + permissions[] from /user)
  └── RbacProvider
        ├── resolveEffectivePermissions(user)
        │     ├── server claims when permissions[] non-empty
        │     └── else ROLE_PRESET_PERMISSIONS fallback (§7.6)
        ├── computeMenu / computeTabAreas
        └── can / hasRole / hasAnyRole / hasAllRoles
```

**Permission-first (build plan §7):** Nav visibility comes from the **effective permission set**, not hard-coded role menus. Role presets supply **fallback bundles** when the API returns an empty `permissions` array.

**Registry:** `AdminPermission` constants in `@erp/core/rbac/permissions.ts` (`<area>.view` pattern).

**Components:**

| Layer | Component | Usage |
| --- | --- | --- |
| `@erp/ui` | `PermissionGate`, `RoleGate`, `Can` | Presentational (`allowed` prop) |
| `apps/admin` | `AppPermissionGate`, `AppRoleGate`, `Can` | Wired to `useCan` / `useRbac` |

---

## 3. Files created

### `@erp/core/src/rbac/`

| File | Purpose |
| --- | --- |
| `permissions.ts` | `AdminPermission` registry, `AREA_VIEW_PERMISSIONS`, dashboard tab permissions |
| `rolePresets.ts` | `RolePreset` enum + `ROLE_PRESET_PERMISSIONS` matrix |
| `roleModel.ts` | `resolveRolePreset`, `presetMatchesRole` |
| `permissionModel.ts` | `resolveEffectivePermissions`, `can`, `canForUser`, `hasRole`, `hasAnyRole`, `hasAllRoles` |
| `computeMenu.ts` | `computeMenu`, `computeTabAreas`, `canAccessArea` |
| `dashboardRules.ts` | `getVisibleDashboardTabs`, `canViewDashboardTab` |
| `RbacContext.tsx` | `RbacProvider`, `useRbac` |
| `hooks.ts` | `useCan`, `useRole`, `usePermissions`, `useHasRole` |
| `index.ts` | Barrel |

### Admin app

| File | Purpose |
| --- | --- |
| `src/features/rbac/components/AppPermissionGate.tsx` | `useCan` → gate |
| `src/features/rbac/components/AppRoleGate.tsx` | `useRbac().hasRole/hasAnyRole/hasAllRoles` → gate |
| `src/features/rbac/components/AppCan.tsx` | Alias |
| `src/features/rbac/index.ts` | Exports `PermissionGate`, `RoleGate`, `Can` |
| `src/navigation/guards/ProtectedAreaScreen.tsx` | Route guard + `withAreaGuard` HOC |
| `src/navigation/areaRoutes.ts` | Area key → drawer/tab route mapping |
| `src/features/auth/screens/ModuleAccessDeniedScreen.tsx` | In-app module access denied |
| `docs/execution/batch-3-report.md` | This report |

---

## 4. Files modified

| File | Change |
| --- | --- |
| `packages/core/src/index.ts` | Export `./rbac` |
| `packages/ui/src/index.ts` | Export `./rbac` primitives |
| `apps/admin/App.tsx` | `RbacProvider` inside `AuthProvider` |
| `apps/admin/src/navigation/DrawerContent.tsx` | Menu from `useRbac().drawerAreas` |
| `apps/admin/src/navigation/BottomTabsNavigator.tsx` | Dynamic tabs from `tabAreas` + guards |
| `apps/admin/src/navigation/DrawerNavigator.tsx` | Register only allowed drawer screens + guards |
| `apps/admin/src/features/dashboard/screens/DashboardScreen.tsx` | Dashboard tab visibility (rules only) |
| `apps/admin/src/features/auth/index.ts` | Export `ModuleAccessDeniedScreen` |

---

## 5. API surface (deliverables)

### Imperative (non-React)

```typescript
import { can, canForUser, hasRole, hasAnyRole, hasAllRoles, useRbac } from '@erp/core';

// Inside React:
const { can, hasRole, hasAnyRole, hasAllRoles } = useRbac();
can('finance.view');
hasRole(RolePreset.BURSAR);
hasAnyRole(RolePreset.ACCOUNTANT, RolePreset.BURSAR);

// Outside React:
canForUser(user, 'students.view');
```

`can(permission)` on `useRbac()` accepts a single key or array (any match by default; `requireAll: true` for all).

### Hooks

| Hook | Returns |
| --- | --- |
| `useCan(permission, options?)` | `boolean` |
| `useRole()` | `RolePreset \| null` |
| `usePermissions()` | `ReadonlySet<string>` |

Also: `useRbac()` for full context (`drawerAreas`, `tabAreas`, `visibleDashboardTabs`, …).

### Components (admin app)

```tsx
import { PermissionGate, RoleGate, Can } from '../features/rbac';

<Can permission="finance.view" fallback={null}>
  <Button label="Open finance" />
</Can>

<RoleGate anyRoles={[RolePreset.BURSAR, RolePreset.ACCOUNTANT]}>
  ...
</RoleGate>
```

---

## 6. Permission matrix

| Permission key | Nav / feature gated |
| --- | --- |
| `dashboard.view` | Dashboard area + Overview tab |
| `dashboard.approvals.view` | Dashboard Approvals tab |
| `dashboard.alerts.view` | Dashboard Alerts tab |
| `admissions.view` | Admissions drawer item + screen |
| `students.view` | Students tab + screen |
| `academics.view` | Academics drawer + screen |
| `finance.view` | Finance tab + screen |
| `people.view` | People tab + screen |
| `operations.view` | Operations drawer + screen |
| `communication.view` | Communication drawer + screen |
| `reports.view` | Reports drawer + screen |
| `settings.view` | Settings drawer + screen |
| `*` | Super Admin — all areas (wildcard) |

Granular actions (`students.edit`, `payments.record`, maker/checker splits) are **out of scope** for Batch 3 and will extend the same registry in later sprints.

---

## 7. Role preset matrix (fallback)

When `/user` returns `permissions: []`, effective permissions are derived from `resolveRolePreset(role, roleName)` → `ROLE_PRESET_PERMISSIONS`.

| Role preset | Dashboard | Admissions | Students | Academics | Finance | People | Operations | Communication | Reports | Settings |
| --- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **Super Admin** (`*`) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| **Director / Principal / Admin** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| **Deputy Principal** | ✓ | ✓ | ✓ | ✓ | — | ✓ | — | ✓ | ✓ | — |
| **Senior Teacher** | ✓ | — | ✓ | ✓ | — | — | — | — | ✓ | — |
| **Teacher** | ✓ | — | ✓ | — | — | — | — | — | — | — |
| **Accountant / Bursar** | ✓ | — | ✓ | — | ✓ | — | — | — | ✓ | — |
| **Secretary** | ✓ | ✓ | ✓ | — | — | ✓ | — | ✓ | — | — |
| **Receptionist** | ✓ | ✓ | — | — | — | — | ✓ | ✓ | — | — |
| **Librarian / Nurse / Store Keeper / Driver / Security Officer** | ✓ | — | — | — | — | — | ✓ | — | — | — |

**Backend role mapping examples** (see `roleModel.ts`): `super_admin` → Super Admin; `admin` → Admin; `secretary` → Secretary; `accountant` → Accountant; `finance` / bursar labels → Bursar; `academic_admin` / head teacher → Deputy Principal; teacher-like roles → Teacher / Senior Teacher.

**Note:** Teacher / Driver presets are for **fallback completeness**; the app-mismatch guard (Batch 2) still blocks non–Admin App roles from entering this binary.

---

## 8. Navigation & routing behaviour

| Concern | Implementation |
| --- | --- |
| **Drawer menu** | `DrawerContent` maps `useRbac().drawerAreas` (from `computeMenu`) |
| **Bottom tabs** | `BottomTabsNavigator` registers only `tabAreas`; initial tab = first allowed |
| **Hidden modules** | Routes not registered in navigator when area not in menu |
| **Deep link / stale route** | `withAreaGuard` wraps each screen → `ModuleAccessDeniedScreen` |
| **No tabs assigned** | Fallback message: use drawer menu |
| **Drawer-only users** | `Workspace` omitted; initial route = first drawer screen |

---

## 9. Dashboard visibility rules

Without widgets, `DashboardScreen` lists gated tabs via `getVisibleDashboardTabs`:

| Tab | Required (any of) |
| --- | --- |
| Overview | `dashboard.view` |
| Approvals | `dashboard.approvals.view` or `dashboard.view` |
| Alerts | `dashboard.alerts.view` or `dashboard.view` |

Sprint 2 will attach widgets to the same permission keys.

---

## 10. Backend dependencies

| Dependency | Status | Notes |
| --- | --- | --- |
| `GET /user` returns `permissions: string[]` | **Exists** (Spatie names via `getAllPermissions()`) | When non-empty, **server claims override** client fallback |
| Canonical permission names aligned with `AdminPermission` | **Partial / evolving** (E4) | Until aligned, empty array triggers preset fallback |
| Branch-scoped permission resolution | **Future** (E1/E2) | Client ready; `can(opts.branchId)` not wired yet |
| Per-route API authorization | **Required** (audit W3) | Client hides UI only; API must enforce |

**No new Laravel endpoints** were required for Batch 3.

---

## 11. Risks

| Risk | Mitigation |
| --- | --- |
| **Server permission names ≠ client registry** | Fallback presets; document canonical keys; flip to server-only when E4 stabilizes |
| **Over-broad fallback for `admin` role** | Leadership preset is intentional for shell testing; tighten per-tenant when server claims land |
| **Client-only security** | Module gate + docs stress API enforcement; never expand access beyond server in production flag |
| **Dynamic navigators vs deep links** | Unregistered routes inaccessible; guarded screens catch edge navigations |
| **Teacher preset in matrix** | Does not bypass app-mismatch guard; preset exists for future RBAC testing only |

---

## 12. Blockers

None for Batch 3 delivery. **Operational:** validate with real `/user` permission payloads once E4 naming is frozen; adjust `AdminPermission` keys or add a server→client alias map in a follow-up batch if names differ (e.g. `view_students` vs `students.view`).

---

## 13. Provider tree (updated)

```text
SessionProvider
  AuthProvider
    RbacProvider
      GoogleAuthProvider
        BiometricAuthProvider
          AdminRootNavigator
            RootGate (auth / app mismatch / biometric enrollment)
            DrawerNavigator (permission-filtered)
```

---

## 14. What is explicitly out of scope

- Business screens, API modules, TanStack Query data layers  
- Dashboard widgets and Approval Center UI (Sprint 2)  
- Branch `ScopeContext` and branch-scoped `can({ branchId })`  
- Settings → Roles & Permissions admin UI  
- Server permission name migration / alias layer (recommended next if API uses Spatie names verbatim)
