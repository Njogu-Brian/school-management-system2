# School Management System - Detailed UI/UX Specification

### Dynamic Branding Colors
| Token | Source Setting Key | Default |
|-------|--------------------|---------|
| `--settings-primary` | `finance_primary_color` | `#0f766e` |
| `--settings-primary-dark` | `finance_primary_color` (darkened) | `#0b5c54` |
| `--settings-accent` | `finance_secondary_color` | `#14b8a6` |
| `--settings-bg` | static | `#f5f7fb` |
| `--settings-border` | `finance_border_color` | `#e5e7eb` |
| `--settings-text` | `finance_text_color` | `#0f172a` |
| `--settings-muted` | `finance_muted_color` | `#6b7280` |
| `--settings-surface` | `finance_surface_color` | `#ffffff` |

- These CSS variables are injected in settings pages and should power buttons, chips, cards, and tabs for the settings shell.
- When a setting is missing, fall back to the default hex shown above.
- Dark mode overrides (activated by `body.theme-dark`):
  - `--settings-bg: #0b1220`
  - `--settings-surface: #111827`
  - `--settings-border: #1f2937`
  - `--settings-text: #e5e7eb`
  - `--settings-muted: #9ca3af`
  - Shadow: `0 10px 30px rgba(0, 0, 0, 0.35)`
- The global layout also exposes `--brand-*` vars (primary/accent/bg/surface/border/text/muted) derived from finance branding; dark mode toggles `body.theme-dark` to swap the base surfaces/borders/text.
- Implement toggle in a sticky bar (top-right within page content):
  - “Dark” switch: toggles `theme-dark`, persisted in `localStorage.themeMode`.
  - Pill style with icons + animated thumb.

## 1. BUTTON SYSTEM

### Button Variants

| Variant | Use Case | Visual Style |
|---------|----------|--------------|
| **Primary** | Main CTAs (Save, Submit, Post Fees) | Solid teal background, white text, subtle shadow |
| **Secondary** | Secondary actions (Cancel, Back) | Teal outline, transparent bg, teal text |
| **Ghost** | Tertiary actions (View, Details) | No border, teal text, hover: light teal bg |
| **Destructive** | Delete, Remove, Reverse | Red bg, white text |
| **Success** | Confirm, Approve, Complete | Green bg, white text |
| **Warning** | Actions needing attention | Amber bg, dark text |
| **Link** | Inline text actions | Underline on hover, no padding |

### Button Sizes

| Size | Height | Padding | Font | Use Case |
|------|--------|---------|------|----------|
| **xs** | 28px | 8px 12px | 12px | Inline table actions, badges |
| **sm** | 32px | 10px 16px | 13px | Secondary actions, filters |
| **md** | 40px | 12px 20px | 14px | Default - most buttons |
| **lg** | 48px | 14px 28px | 16px | Primary page CTAs |
| **xl** | 56px | 16px 32px | 18px | Hero actions, onboarding |

### Button States
- **Default:** Base styling
- **Hover:** Slight darken (5%), subtle lift shadow
- **Active/Pressed:** Scale 0.98, darker shade
- **Disabled:** 50% opacity, cursor not-allowed
- **Loading:** Spinner icon replacing text, disabled state

### Icon Buttons
- **Icon-only:** Square aspect ratio, tooltip required
- **Icon + Text:** Icon left (16px), 8px gap, text right
- **Text + Icon:** Text left, icon right (for dropdowns/arrows)

---

## 2. CONTAINER SYSTEM

### Card Containers

| Type | Use Case | Styling |
|------|----------|---------|
| **Base Card** | General content wrapper | White bg, 1px border, 12px radius, 24px padding |
| **Elevated Card** | Important/highlighted content | Base + box-shadow (0 4px 12px rgba) |
| **Interactive Card** | Clickable cards (student list) | Elevated + hover: translate-y -2px, shadow increase |
| **Stat Card** | KPI/metric display | Elevated + accent left border (4px) |
| **Alert Card** | Warnings, notifications | Colored left border (4px), tinted background |
| **Glass Card** | Overlays, floating panels | Semi-transparent bg, backdrop-blur |

### Card Padding Scale
- **Compact:** 12px - Dense data displays
- **Default:** 20px - Standard cards
- **Spacious:** 32px - Hero sections, empty states

### Page Containers
```
┌─────────────────────────────────────────────────────────────┐
│ Page Header Container (sticky)                              │
│ - Breadcrumb                                                │
│ - Page Title + Description                                  │
│ - Primary Action Buttons                                    │
├─────────────────────────────────────────────────────────────┤
│ Filters Container (collapsible)                             │
│ - Search input                                              │
│ - Filter dropdowns                                          │
│ - Date range picker                                         │
│ - Clear/Apply buttons                                       │
├─────────────────────────────────────────────────────────────┤
│ Content Container                                           │
│ - Table / Grid / Form                                       │
│ - Pagination                                                │
├─────────────────────────────────────────────────────────────┤
│ Footer Container (optional)                                 │
│ - Bulk action bar (when items selected)                     │
│ - Summary statistics                                        │
└─────────────────────────────────────────────────────────────┘
```

### Layout Grids
- **12-column grid** for complex layouts
- **Gap:** 16px (sm), 24px (md), 32px (lg)
- **Max-width:** 1400px centered with auto margins
- **Sidebar width:** 280px expanded, 64px collapsed

---

## 3. FORM SYSTEM

### Input Types

| Input | Features | Validation |
|-------|----------|------------|
| **Text** | Floating label, clear button, character count | Required, min/max length, pattern |
| **Email** | Email icon, domain suggestions | Email format validation |
| **Password** | Show/hide toggle, strength meter | Min length, complexity rules |
| **Number** | Increment/decrement buttons, min/max | Range validation |
| **Currency** | Currency symbol prefix, thousand separators | Positive numbers, decimal places |
| **Phone** | Country code dropdown, format mask | Phone pattern validation |
| **Search** | Magnifier icon, clear button, debounced | N/A |
| **Textarea** | Auto-resize, character count, markdown preview | Max length |

### Input Sizes
| Size | Height | Font | Label | Use |
|------|--------|------|-------|-----|
| **sm** | 36px | 13px | 11px | Compact forms, filters |
| **md** | 44px | 14px | 12px | Default |
| **lg** | 52px | 16px | 13px | Important inputs, onboarding |

### Input States
- **Default:** Gray border, white bg
- **Focus:** Teal border (2px), subtle teal glow
- **Filled:** Darker border, floating label stays up
- **Error:** Red border, red label, error message below
- **Success:** Green border, checkmark icon
- **Disabled:** Gray bg, reduced opacity, not editable
- **Read-only:** White bg, no border, just underline

### Select/Dropdown Components

| Type | Features |
|------|----------|
| **Basic Select** | Native feel, icon, placeholder |
| **Searchable Select** | Type-to-filter, highlight matches |
| **Multi-Select** | Chips for selected items, "Select All" |
| **Grouped Select** | Optgroup headers, indented items |
| **Async Select** | Loading state, fetch on scroll/search |
| **Creatable Select** | "Add new" option at bottom |

### Date/Time Inputs

| Component | Features |
|-----------|----------|
| **Date Picker** | Calendar popup, quick-select (Today, This Week) |
| **Date Range** | Two calendars, presets (This Term, This Year) |
| **Time Picker** | 12/24h toggle, minute increments |
| **DateTime** | Combined picker with tabs |
| **Academic Year Picker** | Shows years like "2024-2025" |
| **Term Picker** | Term 1, Term 2, Term 3 with dates |

### Checkbox & Radio

| Type | Variants |
|------|----------|
| **Checkbox** | Default, indeterminate (for "select all"), card-style |
| **Radio** | Default, card-style (visual selection) |
| **Switch/Toggle** | On/Off states, loading state, with labels |

### Form Layouts

```
┌─────────────────────────────────────────────────────────────┐
│ SINGLE COLUMN (Simple forms)                                │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Label                                                   │ │
│ │ [Input Field                                          ] │ │
│ │ Helper text or error message                            │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ TWO COLUMN (Standard forms)                                 │
│ ┌──────────────────────┐  ┌──────────────────────┐         │
│ │ First Name           │  │ Last Name            │         │
│ │ [                  ] │  │ [                  ] │         │
│ └──────────────────────┘  └──────────────────────┘         │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ SPLIT PANE (Complex entry - e.g., Payment)                  │
│ ┌─────────────────┐  ┌───────────────────────────────────┐ │
│ │ Student Search  │  │ Payment Details                   │ │
│ │ ─────────────── │  │ ┌───────────────────────────────┐ │ │
│ │ [Search...    ] │  │ │ Amount: [          ] KES     │ │ │
│ │                 │  │ │ Method: [Cash        ▼]      │ │ │
│ │ • John Doe      │  │ │ Date:   [12/18/2025   📅]    │ │ │
│ │   Grade 5 │ $500│  │ │ Reference: [TXN-001234    ]  │ │ │
│ │ • Jane Doe      │  │ └───────────────────────────────┘ │ │
│ │   Grade 3 │ $300│  │ ┌───────────────────────────────┐ │ │
│ │                 │  │ │ Siblings (Share Payment)      │ │ │
│ └─────────────────┘  │ │ ☐ Mary Doe - Grade 2 - $200  │ │ │
│                      │ └───────────────────────────────┘ │ │
│                      └───────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Form Sections & Fieldsets
- **Section Header:** Title + optional description + collapse toggle
- **Dividers:** Subtle gray line or whitespace (24px)
- **Fieldset:** Grouped inputs with legend header
- **Accordion Sections:** For long forms (Student Registration)

### Form Actions
```
┌─────────────────────────────────────────────────────────────┐
│ Form Footer (sticky on mobile)                              │
│                                                             │
│            [Cancel]  [Save as Draft]  [Submit ▶]           │
│                                                             │
│ Left: Secondary actions    Right: Primary action (bold)     │
└─────────────────────────────────────────────────────────────┘
```

---

## 4. DATA TABLE SYSTEM

### Table Variants

| Type | Use Case |
|------|----------|
| **Default** | Standard data display |
| **Compact** | Dense data, many rows |
| **Striped** | Easier row scanning |
| **Bordered** | Financial data, precise alignment |
| **Hoverable** | Interactive rows with actions |

### Table Features

| Feature | Implementation |
|---------|----------------|
| **Sticky Header** | Fixed on scroll, shadow indicator |
| **Sorting** | Click header, icon indicator (▲▼) |
| **Filtering** | Per-column filter dropdowns or global search |
| **Column Resize** | Drag column borders |
| **Column Toggle** | Dropdown to show/hide columns |
| **Row Selection** | Checkbox column, select all, range select |
| **Row Expansion** | Chevron to show details inline |
| **Inline Edit** | Click cell to edit, Enter to save |
| **Row Actions** | Hover reveals action icons (Edit, Delete, View) |
| **Bulk Actions** | Floating bar when rows selected |
| **Pagination** | Page numbers, per-page selector, total count |
| **Empty State** | Illustration + message + CTA |
| **Loading State** | Skeleton rows matching column widths |

### Column Types

| Type | Alignment | Features |
|------|-----------|----------|
| **Text** | Left | Truncate with tooltip, copy button |
| **Number** | Right | Thousand separators |
| **Currency** | Right | Symbol, color for negative |
| **Date** | Left | Relative ("2 days ago") + tooltip for full |
| **Status** | Center | Badge with color coding |
| **Actions** | Right | Icon buttons, overflow menu |
| **Avatar** | Left | Image + name combined |
| **Checkbox** | Center | For selection |
| **Progress** | Left | Progress bar with percentage |

### Table Row States
- **Default:** White bg
- **Hover:** Light gray bg (#fafafa)
- **Selected:** Light teal bg, checkbox checked
- **Expanded:** Bottom border removed, details below
- **Disabled:** Grayed out text, no interactions
- **Highlighted:** Yellow bg pulse (after update)

---

## 5. MODAL & DIALOG SYSTEM

### Modal Sizes

| Size | Width | Use Case |
|------|-------|----------|
| **sm** | 400px | Confirmations, simple prompts |
| **md** | 560px | Single-purpose forms |
| **lg** | 720px | Complex forms, data display |
| **xl** | 900px | Multi-step wizards |
| **full** | 95vw | Data tables, file previews |

### Modal Anatomy
```
┌─────────────────────────────────────────────────────────────┐
│ ╔═══════════════════════════════════════════════════════╗   │
│ ║ Header                                            [X] ║   │
│ ║ Title + optional subtitle                             ║   │
│ ╠═══════════════════════════════════════════════════════╣   │
│ ║ Body (scrollable if overflow)                         ║   │
│ ║                                                       ║   │
│ ║ - Form fields                                         ║   │
│ ║ - Content                                             ║   │
│ ║ - Tables                                              ║   │
│ ║                                                       ║   │
│ ╠═══════════════════════════════════════════════════════╣   │
│ ║ Footer (sticky)                                       ║   │
│ ║                         [Cancel]  [Primary Action]    ║   │
│ ╚═══════════════════════════════════════════════════════╝   │
│                      (Backdrop with blur)                   │
└─────────────────────────────────────────────────────────────┘
```

### Dialog Types

| Type | Purpose | Buttons |
|------|---------|---------|
| **Alert** | Information only | [OK] |
| **Confirm** | Yes/No decision | [Cancel] [Confirm] |
| **Destructive** | Delete confirmation | [Cancel] [Delete] (red) |
| **Prompt** | Single input needed | [Cancel] [Submit] |
| **Form** | Data entry | [Cancel] [Save] |
| **Wizard** | Multi-step process | [Back] [Next]/[Finish] |

### Sheet/Drawer (Side Panel)

| Position | Width | Use Case |
|----------|-------|----------|
| **Right** | 400-600px | Quick edit, details view |
| **Right Wide** | 800px | Complex forms, preview |
| **Bottom** | 50vh | Mobile filters, actions |

---

## 6. NAVIGATION COMPONENTS

### Sidebar Navigation
```
┌────────────────────────────────────────┐
│ [Logo] School Name              [◀]   │  ← Collapse toggle
├────────────────────────────────────────┤
│                                        │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │  ← Active indicator (left bar)
│ ┃ 📊  Dashboard                        │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │
│   👥  Students                    ▸   │  ← Expandable submenu
│   📚  Academics                   ▸   │
│   💰  Finance                     ▾   │  ← Expanded
│        ├ Fee Structures               │
│        ├ Invoices                     │
│        ├ Payments                     │
│        ├ Discounts                    │
│        └ Reports                      │
│   🚌  Transport                       │
│   📖  Library                         │
│   🏠  Hostel                          │
│   👔  HR & Payroll                    │
│   📦  Inventory                       │
│   📱  POS                             │
│                                        │
├────────────────────────────────────────┤
│ ⚙️  Settings                          │
│ ❓  Help & Support                    │
└────────────────────────────────────────┘
```

### Breadcrumbs
```
Home / Finance / Invoices / INV-2024-0001
  ↑      ↑         ↑            ↑
link   link      link       current (not link)
```

### Tabs
| Type | Use Case |
|------|----------|
| **Underline Tabs** | Page sections (most common) |
| **Pill Tabs** | Filter toggles |
| **Card Tabs** | Distinct content sections |
| **Vertical Tabs** | Settings pages, long lists |

### Pagination
```
┌──────────────────────────────────────────────────────────────┐
│ Showing 1-10 of 156 results        [◀] [1] [2] [3] ... [16] [▶] │
│                                                              │
│ Show: [10 ▼] per page              Go to page: [  ] [Go]     │
└──────────────────────────────────────────────────────────────┘
```

---

## 7. FEEDBACK & STATUS COMPONENTS

### Toast Notifications

| Type | Color | Icon | Duration |
|------|-------|------|----------|
| **Success** | Green | ✓ Checkmark | 3s auto-dismiss |
| **Error** | Red | ✕ X mark | Manual dismiss |
| **Warning** | Amber | ⚠ Triangle | 5s auto-dismiss |
| **Info** | Blue | ℹ Info | 4s auto-dismiss |
| **Loading** | Gray | Spinner | Until complete |

### Toast Position
- **Desktop:** Top-right, stacked
- **Mobile:** Bottom-center, full-width

### Status Badges

| Status | Color | Example Use |
|--------|-------|-------------|
| **Active/Paid** | Green | Payment status, active students |
| **Pending** | Amber | Pending approval, processing |
| **Overdue/Error** | Red | Overdue fees, failed actions |
| **Partial** | Blue | Partial payment |
| **Inactive** | Gray | Inactive records |
| **Draft** | Outline | Unpublished items |

### Progress Indicators

| Type | Use Case |
|------|----------|
| **Linear Progress** | File uploads, form completion |
| **Circular Progress** | Loading states |
| **Step Indicator** | Multi-step wizards |
| **Skeleton** | Content loading placeholders |

---

## 8. MODULE-SPECIFIC FEATURES

### Dashboard Features
- **KPI Cards:** Total Collected, Outstanding, This Term collections
- **Collection Trend Chart:** Area chart with term comparison
- **Fee Status Donut:** Paid vs Partial vs Unpaid
- **Recent Transactions:** Table with quick actions
- **Alerts Panel:** Overdue fees, pending approvals
- **Quick Actions:** Post Fees, Record Payment, Generate Report
- **Calendar Widget:** Upcoming due dates, events

### Student Management Features
- **Student Directory:** Card grid + list view toggle
- **Student Profile:** Tabbed interface (Info, Fees, Academics, Documents)
- **Quick Search:** Global student search with autocomplete
- **Bulk Import:** CSV upload with column mapping
- **Family Linking:** Visual family tree, sibling management
- **Photo Upload:** Crop/resize, placeholder avatar

### Fee Structure Features
- **Structure Builder:** Drag-drop voteheads into structure
- **Term Matrix:** View all terms side-by-side
- **Class Replication:** Multi-select classes to copy to
- **Version History:** Timeline of structure changes
- **Comparison View:** Side-by-side structure diff
- **Template Library:** Save and reuse common structures

### Fee Posting Features
- **Parameter Selection:** Cascading filters (Year → Term → Class)
- **Preview Mode:** Color-coded diff table
- **Change Summary:** Total new charges, modifications, removals
- **Student Breakdown:** Expandable rows per student
- **Dry Run:** Preview without committing
- **Batch Progress:** Progress bar during posting
- **Rollback Option:** One-click reversal

### Invoice Features
- **Invoice List:** Sortable table with status filters
- **Invoice Detail:** Full breakdown with payment history
- **Inline Edit:** Click amount to modify
- **Credit/Debit Notes:** Automatic creation on changes
- **PDF Preview:** In-modal preview before download
- **Bulk Actions:** Send reminders, export, delete

### Payment Features
- **Student Lookup:** Autocomplete with balance display
- **Sibling Panel:** Collapsible panel showing family members
- **Payment Split:** Visual sliders or amount inputs per sibling
- **Method Selector:** Visual cards with icons
- **Bank Account Dropdown:** Linked to payment method
- **Overpayment Warning:** Yellow alert with carry-forward option
- **Receipt Preview:** Modal preview before confirmation
- **Quick Receipt:** Print-friendly popup window

### Reports Features
- **Report Builder:** Drag-drop columns, filters
- **Saved Reports:** Template library
- **Scheduled Reports:** Email reports on schedule
- **Export Options:** PDF, Excel, CSV
- **Date Range Presets:** This Term, This Year, Custom
- **Drill-Down:** Click totals to see details
- **Charts:** Embedded visualizations

---

## 9. RESPONSIVE BREAKPOINTS

| Breakpoint | Width | Layout Changes |
|------------|-------|----------------|
| **Mobile** | <640px | Single column, bottom nav, stacked cards |
| **Tablet** | 640-1024px | 2-column, collapsible sidebar, condensed tables |
| **Desktop** | 1024-1440px | Full sidebar, 3+ columns, all features |
| **Wide** | >1440px | Centered max-width, extra whitespace |

### Mobile-Specific Patterns
- **Bottom Navigation:** 5 icons + "More" sheet
- **Pull-to-Refresh:** On list pages
- **Swipe Actions:** Swipe row for quick actions
- **Floating Action Button:** Primary action (+ Add)
- **Full-Screen Modals:** Forms take full screen
- **Touch-Friendly:** 44px minimum touch targets

---

## 10. ACCESSIBILITY FEATURES

- **Keyboard Navigation:** All interactive elements focusable
- **Focus Indicators:** Visible focus rings (teal outline)
- **Screen Reader:** ARIA labels on all components
- **Color Contrast:** WCAG AA compliant (4.5:1 minimum)
- **Error Announcements:** Live regions for form errors
- **Skip Links:** "Skip to main content" link
- **Reduced Motion:** Respect prefers-reduced-motion

---
