# Navigation Guide — Design System V3

## Shell architecture

```
Root
 └─ Auth stack (Login, Biometric, Access Denied)
 └─ App shell
     ├─ Drawer (secondary areas) — compact + frosted glass
     └─ Bottom tabs (Workspace) — floating soft-3D bar
         ├─ Dashboard stack
         ├─ Students stack
         ├─ Finance stack
         └─ People stack
     Drawer destinations (stacks):
         Approvals · Admissions · Academics · Operations ·
         Communication · Reports · Settings
```

Sources: `mobile-app/apps/admin/src/navigation/` (`DrawerNavigator`, `BottomTabsNavigator`, `*StackNavigator`).

## Bottom tabs (Workspace)

| Tab | Role |
|-----|------|
| Dashboard | Command center |
| Students | Registry + 360 |
| Finance | Money workflows |
| People | Staff / HR |

Drawer-only routes (Approvals, Academics, Settings, …) also show the floating workspace tab bar via `withWorkspaceTabBar` so primary navigation is always reachable.

### V3 chrome rules (flagship)

- **Floating pill bar** inset from edges (`marginHorizontal` ≥ 16), elevated above content
- **Frosted / translucent** surface (`BlurView` on iOS + Android `dimezisBlurView`) + soft border — not a flat opaque strip
- Soft-3D icons for every tab (active + inactive); active lifts with primary ring / stronger sheen
- Labels: `tiny` / `caption`, primary when focused, muted when idle
- Min height 56 + safe area; `ScreenContainer` applies `FLOATING_TAB_BAR_CLEARANCE` so content is not covered
- Do **not** use stock Material default tab chrome
- Global “Search anything…” prompt appears on **Dashboard** header only (local list search bars remain on feature screens)

## Drawer

| Spec | Value |
|------|-------|
| Width | **~72% of screen**, prefer **≤280dp** on phones — never full-bleed wall |
| Surface | **Frosted glass** (`BlurView` on iOS + Android) + light primary wash — not solid white |
| Scrim | Dimmed backdrop (`opacity.scrim`) over the main shell |
| Active row | Soft-3D icon + `primaryMuted` pill background + primary label |
| Inactive row | Soft-3D icon (muted tone) + `textMain` label |
| Header | Compact branding (logo 32–36) — not a second dashboard |

## Top app bar / headers

| Context | Pattern |
|---------|---------|
| Tab roots | `GlobalAppHeader` — menu, title/branch, Soft3D approvals + notifications, profile; search on Dashboard only |
| Stack children | Shared screen header: back (Ionicons), title `title`, optional actions |
| Module hubs | `DashboardHero` below header (or integrated) |
| Finance / Academics subflows | Domain headers — migrate to shared `ScreenHeader` |
| 360 profiles | Collapsing header + `ScrollableTabBar`; avoid double back bars |

**V3:** One `ScreenHeader` primitive; domain headers become thin wrappers. No Unicode `←`.

## Push vs sheet vs modal

| Use | Pattern |
|-----|---------|
| Drill into entity | Stack **push** |
| Filters, quick actions, pickers | **Bottom sheet** (`FilterBottomSheet` pattern) |
| Confirm destructive / branded success | **Dialog** (shared primitive) |
| Session / About from Settings | Modal or sheet — keep hub uncluttered |
| M-Pesa / payment prompt | Sheet (existing `MpesaPromptSheet`) |

## Transitions

See [ANIMATION_GUIDE.md](./ANIMATION_GUIDE.md): stack slide slow; sheets emphasized; dialogs scale+fade.

## Deep links & search

- `GlobalSearchScreen` pushes entity routes into the correct stack when possible
- Preserve back stack sanity — don't open orphan modals for core entities

## RBAC & blocked navigation

- Hidden routes preferred over disabled dead-ends when possible
- Access denied: branded `EmptyState` / `AccessDeniedScreen` pattern — not raw text

## Tablet

- Prefer list + detail master-detail for registries when width ≥ 768
- Bottom tabs may move to side rail later; V3 Phase 1 docs require layouts not to assume 360-only width
- Touch targets remain ≥ 44dp

## Do / Don't

| Do | Don't |
|----|-------|
| Compact frosted drawer | Full-width solid white drawer |
| Floating frosted tab bar + soft-3D icons | Flat Material tabs with outline-only icons |
| One header system | Stack header + custom Unicode back + 360 title bar |
| Sheets for filters | Five always-visible filter rows above lists |
| Permission-aware menus | Showing locked modules as broken screens |
| Safe-area aware nav | Content under gesture/nav bars |
