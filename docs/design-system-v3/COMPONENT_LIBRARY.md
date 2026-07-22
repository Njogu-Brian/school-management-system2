# Component Library — Design System V3

> Contract for reusable UI in `@erp/ui`. Status: **EXISTS** (upgrade to V3 tokens) · **PARTIAL** · **NEW (Stage 1)**.

All components must: use tokens only · support light/dark · expose loading/disabled where interactive · meet 44–48dp targets · pass [DESIGN_REVIEW_CHECKLIST.md](./DESIGN_REVIEW_CHECKLIST.md).

---

## 1. Primitives

### Button — EXISTS `primitives/Button.tsx`

| Variant | Use |
|---------|-----|
| Primary | Main CTA (filled primary) |
| Secondary | Secondary filled / muted |
| Outlined | Tertiary actions |
| Ghost / text | Inline low-emphasis |
| Destructive | Danger tone filled or outlined |
| Loading | Spinner replaces label; keeps width |

**States:** default · pressed · disabled · loading  
**Radius:** `control` (18) · Min height 48 · Label: `typography.button`  
**V3:** LoadingButton explicit variant; haptic optional on success actions.

### TextField — EXISTS `primitives/TextField.tsx`

Anatomy: label (`label`) · field (`surfaceRaised`, radius control) · helper/error (`caption`)  
States: default · focused (primary border) · error · disabled · read-only  
Prefixes/suffixes: currency, icons — token-colored.

### SearchBar — EXISTS `primitives/SearchBar.tsx`

Height 48 · raised surface · search icon · clear affordance · a11y role search  
V3: sticky-capable wrapper for registries.

### FilterChip / FilterChipRow — EXISTS `primitives/FilterChip.tsx`

Pill (`radius.full`) · active = primaryMuted + primary text · inactive = muted  
V3: all filter UIs must use this (Staff, Approvals, Exams, Executive period).

### StatusBadge — EXISTS `primitives/StatusBadge.tsx`

Tones: brand · success · warning · danger · info · compact flag  
V3: migrate Application/Exam/Approval priority badges to wrap this.

---

## 2. Feedback & states

| Component | Path | Status | Notes |
|-----------|------|--------|-------|
| EmptyState | `feedback/EmptyState.tsx` | EXISTS | Icon well + title + body + CTA — **mandate on all lists** |
| ListEmptyState | `feedback/ListEmptyState.tsx` | EXISTS | FlatList-friendly wrapper |
| OfflineBanner | `feedback/OfflineBanner.tsx` | EXISTS | Persistent top/stack banner |
| SkeletonLoader | `feedback/SkeletonLoader.tsx` | EXISTS | Match layout |
| SkeletonListRows | `feedback/SkeletonListRows.tsx` | EXISTS | Registry default loading |
| PlaceholderScreen | `feedback/PlaceholderScreen.tsx` | EXISTS | Unbuilt surfaces |
| AppErrorBoundary | `feedback/AppErrorBoundary.tsx` | EXISTS | Branded fallback |
| Toast / Snackbar | `feedback/Toast.tsx` | **EXISTS** | `ToastProvider` + `useToast` |
| AlertDialog | `feedback/Dialogs.tsx` | **EXISTS** | Branded title, body, primary; card above scrim |
| ConfirmDialog | `feedback/Dialogs.tsx` | **EXISTS** | Destructive emphasis; bright elevated card |
| LoadingDialog | `feedback/Dialogs.tsx` | **EXISTS** | Blocking progress |
| SuccessDialog | `feedback/Dialogs.tsx` | **EXISTS** | Branded success |

**Rule:** Prefer dialogs/sheets over `Alert.alert` for primary workflows.

---

## 3. Layout & chrome

| Component | Path | Status | V3 direction |
|-----------|------|--------|--------------|
| ScreenContainer | `layout/ScreenContainer.tsx` | EXISTS | Token padding; safe area |
| GlobalAppHeader | `layout/GlobalAppHeader.tsx` | PARTIAL | surfaceRaised, typography, elevation |
| ScreenHeader | `layout/ScreenHeader.tsx` | **EXISTS** | Unify Finance/Academic/360 backs |
| RegistryListLayout | `layout/RegistryListLayout.tsx` | EXISTS | Sticky search + filter trigger |
| ScrollableTabBar | `layout/ScrollableTabBar.tsx` | EXISTS | **Use for all 360**; a11y tabs; height ≥ 44 |
| SegmentedTabBar | `dashboard/SegmentedTabBar.tsx` | EXISTS | Dashboard / settings segments |
| Profile360Layout | `layout/Profile360Layout.tsx` | EXISTS | Shared shell; collapsing header |
| Profile360CompactBar | `layout/Profile360CompactBar.tsx` | EXISTS | Sticky compact identity |
| BottomTabBar | `layout/PremiumTabBar.tsx` + `getPremiumTabBarOptions` | **EXISTS** | Custom premium chrome |
| QuickActionFab | admin feature | EXISTS | Token elevation 5 |

---

## 4. Filters & sheets

| Component | Path | Status |
|-----------|------|--------|
| FilterBottomSheet | `filters/FilterBottomSheet.tsx` | EXISTS — prefer over multi-row chip walls |
| FilterTriggerButton | `filters/FilterTriggerButton.tsx` | EXISTS — “Filters (n)” summary |
| Domain filters | students/staff/admissions/finance/approvals/academics | PARTIAL — migrate chips to FilterChip |

---

## 5. Dashboard & analytics

| Component | Path | Status |
|-----------|------|--------|
| DashboardHero | `dashboard/DashboardHero.tsx` | EXISTS — add Students + Settings variants |
| KpiCard | `dashboard/KpiCard.tsx` | EXISTS — optional sparkline/delta slot V3 |
| WidgetShell | `dashboard/WidgetShell.tsx` | EXISTS — loading/error/empty per widget |
| WidgetGrid | `dashboard/WidgetGrid.tsx` | EXISTS |
| ChartCard | `dashboard/ChartCard.tsx` | EXISTS — require a11y summary |
| DashboardSection | `dashboard/DashboardSection.tsx` | EXISTS |
| QuickAction | `dashboard/QuickAction.tsx` | EXISTS |
| AlertCard | `dashboard/AlertCard.tsx` | EXISTS |
| StatisticCard | alias of KpiCard | — use KpiCard |

---

## 6. Domain list & cards

| Component | Path | Status |
|-----------|------|--------|
| StudentListItem | `students/StudentListItem.tsx` | EXISTS — raise to radius.card 24 |
| StaffListItem | `staff/StaffListItem.tsx` | EXISTS |
| ApplicationListItem | `admissions/ApplicationListItem.tsx` | EXISTS |
| InvoiceListItem / PaymentListItem | `finance/*` | EXISTS |
| FinanceTransactionListItem | `finance/*` | EXISTS |
| ExamListItem / MarksRow / ReportCardCard | `academics/*` | EXISTS |
| ApprovalCard | `approvals/ApprovalCard.tsx` | EXISTS — priority left stripe V3 |
| ApprovalActionBar | `approvals/ApprovalActionBar.tsx` | EXISTS |
| ApprovalDetailView | `approvals/ApprovalDetailView.tsx` | EXISTS |
| SettingCard | `settings/SettingCard.tsx` | PARTIAL — typography/elevation sweep |
| SettingsHubLayout | `settings/SettingsHubLayout.tsx` | PARTIAL — add hero |
| Announcement / Message cards | admin communication | PARTIAL — extract to `@erp/ui` if reused |

Thin wrappers only — shared row shell should own radius, padding, chevron, pressed state.

---

## 7. 360 / profile kits

| Kit | Path | Status |
|-----|------|--------|
| Student360 | `student360/*` | PARTIAL — unify tabs via ScrollableTabBar |
| Staff360 | `staff360/*` | PARTIAL |
| Admissions360 | `admissions360/*` | PARTIAL |
| Field sections | FinanceFieldSection, StaffFieldSection, ApplicationFieldSection | EXISTS — align spacing |

---

## 8. RBAC

| Component | Path |
|-----------|------|
| Can / PermissionGate / RoleGate | `rbac/*` |

Denied UI must use EmptyState / AccessDenied patterns.

---

## 9. Component API conventions

```tsx
// Preferred
type Props = {
  // required data
  // optional tone?: SemanticTone
  // loading?: boolean
  // onPress?: () => void
  // testID?: string
};
```

- No hex props — use `tone` or theme
- Forward `testID` for E2E
- Prefer composition over boolean prop explosion

## 10. Do / Don't

| Do | Don't |
|----|-------|
| Extend `@erp/ui` for shared UI | Copy-paste list rows between modules |
| EmptyState on every list | Centered plain `Text` for empty |
| StatusBadge tones | Per-file hex status maps |
| FilterBottomSheet for 3+ dimensions | Five horizontal chip rows always open |
| Branded ConfirmDialog | Bare `Text` “Reject” confirm |
