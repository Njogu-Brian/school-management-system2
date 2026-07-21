# Navigation Guide — Design System V3

## Shell architecture

```
Root
 └─ Auth stack (Login, Biometric, Access Denied)
 └─ App shell
     ├─ Drawer (secondary areas)
     └─ Bottom tabs (Workspace)
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

### V3 chrome rules

- Custom tab bar styling: `surfaceRaised`, soft top border, **not** stock Material look-alike
- Active: primary color + filled icon + indicator
- Inactive: outline icon + `textMuted`
- Min height 56 + safe area; labels `caption`
- Optional future: elevated center action — only if product requires; do not copy competitor “Explore” metaphor blindly

## Drawer

- Group items by IA sections (Approvals, Academics, Operations, etc.)
- Permission-filtered (`withAreaGuard` / RBAC)
- Active row: `primaryMuted` background + primary label
- Header: school/branding snippet — not a second dashboard

## Top app bar / headers

| Context | Pattern |
|---------|---------|
| Tab roots | `GlobalAppHeader` — menu, title/branch, search, notifications |
| Stack children | Shared screen header: back (Ionicons), title `title`, optional actions |
| Module hubs | `DashboardHero` below header (or integrated) |
| Finance / Academics subflows | Domain headers (`FinanceScreenHeader`, `AcademicScreenHeader`) — migrate to shared `ScreenHeader` |
| 360 profiles | Collapsing header + `ScrollableTabBar`; avoid double back bars |

**V3:** One `ScreenHeader` primitive; domain headers become thin wrappers. No Unicode `←`.

## Push vs sheet vs modal

| Use | Pattern |
|-----|---------|
| Drill into entity | Stack **push** |
| Filters, quick actions, pickers | **Bottom sheet** (`FilterBottomSheet` pattern) |
| Confirm destructive / branded success | **Dialog** (shared primitive — NEW) |
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
| One header system | Stack header + custom Unicode back + 360 title bar |
| Sheets for filters | Five always-visible filter rows above lists |
| Permission-aware menus | Showing locked modules as broken screens |
| Safe-area aware nav | Content under gesture/nav bars |
