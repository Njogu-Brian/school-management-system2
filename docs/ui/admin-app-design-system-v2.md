# Admin App Design System V2

> **SUPERSEDED (July 2026).** Canonical design source of truth is now **Design System V3**: [`docs/design-system-v3/README.md`](../design-system-v3/README.md). Keep this file for migration history only — do not extend V2.

> **Scope (historical):** Visual modernization of the Admin App (`mobile-app/apps/admin`) from MVP enterprise styling to a modern, Google Stitch–quality interface. **No workflow, API, or navigation changes.**

**Package:** `@erp/ui` (`mobile-app/packages/ui`)  
**Authored:** June 2026  
**Inputs:** [`docs/admin-app/01-admin-discovery.md`](../admin-app/01-admin-discovery.md), [`03-admin-ui-specifications.md`](../admin-app/03-admin-ui-specifications.md)

---

## 1. Audit Summary (V1 → V2)

### Before (V1 — MVP Enterprise)

| Area | V1 state |
|------|----------|
| **Colors** | Flat `#f0f5fa` background, single `#ffffff` surface, hardcoded Tailwind-like badge colors in some modules |
| **Typography** | Legacy `FONT_SIZES` (12–32px), inconsistent weights, uppercase KPI labels at 12px |
| **Spacing** | 8pt-ish grid (4/8/16/24/32/48), no intermediate steps |
| **Radius** | Mixed: cards `12`, controls `8`, ad-hoc `10` on icon circles |
| **Shadows** | Primary-tinted `shadows.sm/md/lg`, hairline borders on every card |
| **Components** | Duplicated search bars, inline filter chips, no shared hero or tab bar |
| **Dashboards** | Plain text titles ("Finance Dashboard"), no gradient hero, charts only on Executive tab |
| **Visual hierarchy** | Flat — section titles same weight as body, KPI values not differentiated |

### After (V2 — Modern Stitch Quality)

| Area | V2 state |
|------|----------|
| **Colors** | Semantic palette (brand/success/warning/danger/info) + 5-level surface hierarchy |
| **Typography** | Named scale: Display → Caption with line heights and letter spacing |
| **Spacing** | Strict 4pt grid with intermediate tokens (`3xs=12`, `2md=20`, `2lg=40`) |
| **Radius** | Unified scale + semantic aliases (`card=16`, `control=12`, `chip=full`) |
| **Elevation** | 0–4 level system replacing ad-hoc shadows |
| **Components** | Shared `SearchBar`, `FilterChip`, `StatusBadge`, `DashboardHero`, `SegmentedTabBar`, `ChartCard` |
| **Dashboards** | Gradient hero banners on all 5 module hubs + contextual charts |
| **Visual hierarchy** | Hero → tabs → KPI grid → charts → sections → lists |

---

## 2. Design Tokens (V2)

Source: `packages/ui/src/theme/tokens.ts`  
Runtime: `useTheme()` in `packages/ui/src/theme/ThemeContext.tsx`

### 2.1 Colors

#### Brand
| Token | Light | Usage |
|-------|-------|-------|
| `primary` | `#004A99` | Actions, active tabs, chart lines |
| `primaryDark` | `#003366` | Dark-mode hero gradient |
| `primaryLight` | `#1a6bc4` | Light-mode hero gradient end |
| `primaryMuted` | `#e8f1fb` | Icon backgrounds, subtle tints |

#### Semantic
| Role | Foreground | Background (light) |
|------|------------|-------------------|
| **Success** | `#059669` | `#d1fae5` |
| **Warning** | `#d97706` | `#fef3c7` |
| **Danger** | `#dc2626` | `#fee2e2` |
| **Info** | `#2563eb` | `#dbeafe` |

Access via `theme.semantic.success.fg` / `.bg` / `.border` or `StatusBadge` `tone` prop.

#### Surface Hierarchy
| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `background` | `#f4f7fb` | `#0a1628` | Screen canvas |
| `surface` | `#ffffff` | `#111c2e` | Default cards |
| `surfaceRaised` | `#ffffff` | `#162236` | Elevated cards, search bars |
| `surfaceMuted` | `#eef2f7` | `#1a2740` | Tab bar track, empty-state icon bg |
| `surfaceOverlay` | `rgba(255,255,255,0.92)` | `rgba(17,28,46,0.95)` | Sheets (future) |

Palette keys: `palette.background`, `palette.surfaceRaised`, `palette.borderSubtle`, `palette.textMuted`, etc.

### 2.2 Typography

| Role | Size | Line | Weight | Usage |
|------|------|------|--------|-------|
| **Display** | 28 | 34 | 700 | Marketing/onboarding (reserved) |
| **Heading** | 22 | 28 | 700 | Hero titles, KPI values |
| **Title** | 18 | 24 | 600 | Section headers, chart titles |
| **Body** | 15 | 22 | 400/500 | List names, form input |
| **Caption** | 13 | 18 | 500 | Meta text, tab labels |
| **Overline** | 11 | 14 | 600 | KPI labels, filter section labels |

Access: `theme.typography.heading.fontSize`, etc.

Legacy `fontSizes` (xs–xxxl) retained for backward compatibility.

### 2.3 Spacing (4pt Grid)

| Token | px | Usage |
|-------|-----|-------|
| `xs` / `2xs` | 4 | Tight gaps |
| `sm` | 8 | Chip gaps, icon padding |
| `3xs` | 12 | Card internal gaps |
| `md` | 16 | Screen padding, card padding |
| `2md` | 20 | — |
| `lg` | 24 | Section gaps |
| `xl` | 32 | Bottom scroll padding |
| `2lg` | 40 | — |
| `xxl` | 48 | Large section breaks |

### 2.4 Radius

| Token | px | Alias |
|-------|-----|-------|
| `xs` | 4 | — |
| `sm` | 8 | — |
| `md` | 12 | `control` |
| `lg` | 16 | `card` |
| `xl` | 20 | — |
| `2xl` | 24 | Empty-state icon circle |
| `full` | 9999 | `chip` |

### 2.5 Elevation

| Level | Usage |
|-------|-------|
| `0` | Flat inline elements |
| `1` | List items, search bars, tab active pill |
| `2` | KPI cards, chart cards, hero banner |
| `3` | Bottom sheets (future) |
| `4` | Modals (future) |

Legacy `shadows.sm/md/lg` map to elevation 1/2/3.

---

## 3. Component Inventory

### 3.1 New V2 Primitives (`@erp/ui`)

| Component | Path | Description |
|-----------|------|-------------|
| `SearchBar` | `primitives/SearchBar.tsx` | Unified search with icon, raised surface, 48px height |
| `FilterChip` | `primitives/FilterChip.tsx` | Pill filter with active primary tint |
| `FilterChipRow` | `primitives/FilterChip.tsx` | Horizontal scroll row with overline label |
| `StatusBadge` | `primitives/StatusBadge.tsx` | Semantic tone badge (brand/success/warning/danger/info) |

### 3.2 New V2 Dashboard Components

| Component | Path | Description |
|-----------|------|-------------|
| `DashboardHero` | `dashboard/DashboardHero.tsx` | Gradient banner with variant icon, title, subtitle, meta pill |
| `SegmentedTabBar` | `dashboard/SegmentedTabBar.tsx` | Pill-segment control in muted track |
| `ChartCard` | `dashboard/ChartCard.tsx` | Elevated chart wrapper with title/subtitle |

### 3.3 Upgraded Components

| Component | V2 changes |
|-----------|------------|
| `KpiCard` | Heading-size values, overline labels, tinted icon circle |
| `WidgetShell` | `surfaceRaised`, elevation 2, `borderSubtle` |
| `QuickAction` | Vertical icon+label layout, raised card style |
| `DashboardSection` | Title typography scale, caption subtitles |
| `EmptyState` | Larger icon circle (80px), title/body typography, outlined CTA |
| `AlertCard` | Icon in tinted circle, raised surface |
| `TextField` | Raised surface, control radius, focus elevation |
| `StudentListItem` | Raised surface, caption/meta hierarchy |
| `StaffListItem` | Same as student list item |
| `ApplicationListItem` | Same + chevron |
| `StudentSearchBar` | Delegates to `SearchBar` |
| `StaffSearchBar` | Delegates to `SearchBar` |
| `ApplicationSearchBar` | Delegates to `SearchBar` |
| `FinanceSearchBar` | Delegates to `SearchBar` |
| `AcademicSearchBar` | Delegates to `SearchBar` |
| `StudentFilters` | Uses `FilterChip` |
| `ApplicationFilters` | Uses `FilterChip` |
| `StudentStatusBadge` | Delegates to `StatusBadge` |
| `InvoiceStatusBadge` | Delegates to `StatusBadge` |

### 3.4 Admin App Chart Components (new)

| Component | Path | Data source |
|-----------|------|-------------|
| `ExecutiveCharts` | `features/dashboard/components/ExecutiveCharts.tsx` | `useExecutiveAnalytics()` — upgraded with `ChartCard` |
| `FinanceSummaryChart` | `features/finance/components/FinanceSummaryChart.tsx` | Finance dashboard KPIs |
| `ExamBreakdownChart` | `features/academics/components/ExamBreakdownChart.tsx` | `examStatusBreakdown` |
| `AdmissionsFunnelChart` | `features/admissions/components/AdmissionsFunnelChart.tsx` | Admissions stats |

---

## 4. Dashboard Hero Sections

Each module hub now opens with a `DashboardHero` gradient banner.

| Module | Screen | Variant | Meta line |
|--------|--------|---------|-----------|
| **Dashboard** | `DashboardLayout.tsx` | `default` | "Real-time KPIs · Approvals · Alerts" |
| **Finance** | `FinanceDashboardScreen.tsx` | `finance` | Collected-this-month amount |
| **Academics** | `AcademicsDashboardScreen.tsx` | `academics` | Exams in pipeline count |
| **Admissions** | `AdmissionsWorkspaceScreen.tsx` | `admissions` | Total applications |
| **People** | `StaffRegistryScreen.tsx` | `people` | Staff member count |

Hero structure:
```
┌─────────────────────────────────────┐
│ [icon]  Title (Heading 22/bold)     │
│         Subtitle (Body 15)          │
│  ┌─────────────────────────────┐    │
│  │ meta pill (Caption 13)      │    │
│  └─────────────────────────────┘    │
└─────────────────────────────────────┘
  LinearGradient primary → primaryLight
```

---

## 5. Before / After Screenshot References

> Screenshots should be captured from Expo Go or a device build after deploying V2. Place files under `docs/ui/screenshots/v2/`.

| Screen | Before (V1) | After (V2) |
|--------|-------------|------------|
| Dashboard Overview | `docs/ui/screenshots/v1/dashboard-overview.png` | `docs/ui/screenshots/v2/dashboard-overview.png` |
| Dashboard Executive | `docs/ui/screenshots/v1/dashboard-executive.png` | `docs/ui/screenshots/v2/dashboard-executive.png` |
| Finance Dashboard | `docs/ui/screenshots/v1/finance-dashboard.png` | `docs/ui/screenshots/v2/finance-dashboard.png` |
| Academics Dashboard | `docs/ui/screenshots/v1/academics-dashboard.png` | `docs/ui/screenshots/v2/academics-dashboard.png` |
| Admissions Workspace | `docs/ui/screenshots/v1/admissions-workspace.png` | `docs/ui/screenshots/v2/admissions-workspace.png` |
| People Directory | `docs/ui/screenshots/v1/people-directory.png` | `docs/ui/screenshots/v2/people-directory.png` |
| KPI Card | `docs/ui/screenshots/v1/kpi-card.png` | `docs/ui/screenshots/v2/kpi-card.png` |
| Search Bar | `docs/ui/screenshots/v1/search-bar.png` | `docs/ui/screenshots/v2/search-bar.png` |
| Filter Chips | `docs/ui/screenshots/v1/filter-chips.png` | `docs/ui/screenshots/v2/filter-chips.png` |
| Status Badge | `docs/ui/screenshots/v1/status-badge.png` | `docs/ui/screenshots/v2/status-badge.png` |
| Empty State | `docs/ui/screenshots/v1/empty-state.png` | `docs/ui/screenshots/v2/empty-state.png` |
| Segmented Tab Bar | `docs/ui/screenshots/v1/tab-bar.png` | `docs/ui/screenshots/v2/tab-bar.png` |

### Key visual deltas (for Stitch/Figma parity)

1. **Hero gradient** replaces plain `Text` page titles
2. **SegmentedTabBar** replaces inline bordered tab pills on Dashboard
3. **KPI cards** use elevation 2 + overline labels + 22px values
4. **Search bars** are unified 48px raised inputs with search icon
5. **List items** use `surfaceRaised` + elevation 1 instead of flat surface + hairline
6. **Charts** wrapped in `ChartCard` with title typography
7. **Filter chips** share one `FilterChip` primitive across modules
8. **Status badges** use semantic tones via `StatusBadge`

---

## 6. Usage Examples

### Theme access
```tsx
const { palette, typography, elevation, semantic } = useTheme();

<View style={[elevation[2], { backgroundColor: palette.surfaceRaised, borderRadius: 16 }]}>
  <Text style={{ fontSize: typography.heading.fontSize, fontWeight: '700' }}>
    Title
  </Text>
</View>
```

### Dashboard hero
```tsx
<DashboardHero
  variant="finance"
  title="Finance Dashboard"
  subtitle="Collections, billing & reconciliation"
  meta="KES 1.2M collected this month"
/>
```

### Segmented tabs
```tsx
<SegmentedTabBar
  tabs={[
    { key: 'overview', label: 'Overview' },
    { key: 'executive', label: 'Executive' },
  ]}
  activeTab={tab}
  onTabChange={setTab}
/>
```

### Status badge
```tsx
<StatusBadge label="Paid" tone="success" />
<StatusBadge label="Overdue" tone="danger" compact />
```

---

## 7. Migration Notes

- **Backward compatible:** Legacy `fontSizes`, `shadows`, and `COLORS` keys unchanged
- **New theme fields:** `typography`, `elevation`, `semantic`, extended `palette` surface keys
- **Peer dependency:** `expo-linear-gradient` added to `@erp/ui` for `DashboardHero`
- **Export rename:** `ApprovalFilters.FilterChip` → `ApprovalFilterChipOption` (avoids collision with primitive)
- **No API changes:** All data hooks and navigation routes unchanged
- **TypeScript:** `npm run typecheck` in `apps/admin` passes

---

## 8. File Change Index

### `packages/ui`
- `src/theme/tokens.ts` — V2 token definitions
- `src/theme/ThemeContext.tsx` — Extended `ThemeValue`
- `src/primitives/SearchBar.tsx`, `FilterChip.tsx`, `StatusBadge.tsx` — New
- `src/dashboard/DashboardHero.tsx`, `SegmentedTabBar.tsx`, `ChartCard.tsx` — New
- `src/dashboard/KpiCard.tsx`, `WidgetShell.tsx`, `QuickAction.tsx`, `DashboardSection.tsx`, `AlertCard.tsx` — Upgraded
- `src/feedback/EmptyState.tsx` — Upgraded
- `src/primitives/TextField.tsx` — Upgraded
- Domain search bars, list items, filters, badges — Upgraded

### `apps/admin`
- `src/features/dashboard/components/DashboardLayout.tsx` — Hero + SegmentedTabBar
- `src/features/dashboard/components/ExecutiveCharts.tsx` — ChartCard wrapper
- `src/features/finance/screens/FinanceDashboardScreen.tsx` — Hero + chart
- `src/features/academics/screens/AcademicsDashboardScreen.tsx` — Hero + chart
- `src/features/admissions/screens/AdmissionsWorkspaceScreen.tsx` — Hero + chart
- `src/features/people/screens/StaffRegistryScreen.tsx` — Hero
- New chart components in `finance/`, `academics/`, `admissions/` `components/`

---

## 9. Verification Checklist

- [x] Design tokens defined (colors, typography, spacing, radius, elevation)
- [x] Reusable components upgraded (KPI, widgets, empty states, lists, search, filters, badges, tabs)
- [x] Hero sections on Dashboard, Finance, Academics, Admissions, People
- [x] Charts added where data exists (Executive, Finance, Academics, Admissions)
- [x] Visual hierarchy improved (hero → tabs → KPIs → charts → sections)
- [x] No workflow/API/navigation changes
- [x] TypeScript passes (`apps/admin`: `npm run typecheck`)
- [ ] Screenshot capture (manual — place in `docs/ui/screenshots/v2/`)
