# UI/UX Guideline: School Management System (SMS) — ScholarCore

This document outlines the UI/UX principles, patterns, and component standards for all present and future modules of the School Management System, ensuring a unified user experience across web and mobile.

---

## 1. Core Principles

- **Efficiency First:** For Staff and Finance, minimize clicks for repetitive tasks (e.g., mark attendance, record payment).
- **Accessibility:** Ensure high contrast for text and WCAG-compliant color palettes for inclusive use by parents and students.
- **Data Clarity:** Present dense school data (grades, statements, attendance) through clear tables, progress bars, and status labels.
- **Trust & Professionalism:** Use a “Curated Institution” aesthetic—clean, organized, and reliable.

---

## 2. Global Navigation Strategy

To handle the vast functional scope, a tiered navigation system is required:

### Web (Desktop)

- **Primary Sidebar:** Collapsible menu with 12+ top-level modules (Academics, Finance, HR, Transport, etc.).
- **Top Utility Bar:** Search (global student/staff search), Notifications, Profile, and Campus Selector.
- **Secondary Tabbed Navigation:** Inside modules like Finance, use tabs for Invoices, Payments, Voteheads, and Discounts.

### Mobile (Handheld)

- **Bottom Navigation (4–5 Items):** Dashboard, Students, Payments, and More.
- **More Menu Grid:** A dedicated screen with a grid of icons for secondary modules (Library, Inventory, Events, etc.).
- **Contextual Search:** A persistent search bar at the top of list screens for rapid lookup.

---

## 3. Data Presentation & Manipulation

### 3.1 Data Tables (The Backbone)

- **Columns:** Customizable/toggleable columns for staff views.
- **Sticky Headers:** Essential for long lists like student attendance or financial statements.
- **Bulk Actions:** Standardized “Select All” checkbox at the top for batch operations (Bulk Archive, Bulk Assign, Bulk SMS).
- **In-Row Actions:** View, Edit, and Delete/Archive (using a meatball or kebab menu for space efficiency).

### 3.2 Filtering & Search

- **Standard Filter Bar:** Above every list, including Date Range, Class, Stream, and Status.
- **Advanced Filtering:** A slide-out panel for complex queries (e.g., “Students with >5 absences in Term 2”).
- **Live Search:** Debounced search that updates the list as the user types.

---

## 4. Interaction Patterns

### 4.1 Forms & Data Entry

- **Inline Validation:** Real-time feedback for required fields and formatting (especially phone numbers and currency).
- **Steppers:** For multi-step processes like Online Admission or Fee Structure Creation.
- **Autosave:** For long-form content like Lesson Plans or Schemes of Work.

### 4.2 Feedback & Status

- **Success Toasts:** Brief confirmation at the top-right after saving or deleting.
- **Destructive Action Confirmation:** Modals for all Delete, Archive, or Reverse Payment actions.
- **Empty States:** Custom illustrations and call-to-action buttons when a list is empty (e.g., “No students found. Add your first student”).

### 4.3 Future-Proofing (Scalability)

- **Modular Sections:** Design pages as a collection of cards. New features (e.g., Alumni Tracking or Donations) should be added as new cards or module-level tabs.
- **Configurable Dashboards:** Allow users to pin/unpin widgets based on their role (e.g., a Finance Officer does not need the Lesson Plan Approval widget).

---

## 5. Visual Standards (Theme Reference)

| Token | Value |
|--------|--------|
| **Primary color** | `#004A99` (ScholarCore Blue) |
| **Font** | Inter (sans-serif) for high legibility |
| **Borders** | `1px` subtle gray `#E5E7EB` |
| **Corner radius** | `4px` (`ROUND_FOUR`) for cards and buttons |
| **Spacing** | `16px` (`1rem`) gutter between elements |

Mobile app implementation: see `mobile-app/src/constants/theme.ts` and `designTokens.ts`.

---

## 6. Future Functional Mapping

| Future Module Category | Recommended UI Pattern |
|------------------------|-------------------------|
| **Asset Tracking** | Inventory list with QR/barcode scanner button on mobile. |
| **Parent/Teacher Chat** | Real-time messaging UI with status indicators (Sent, Delivered, Read). |
| **Analytics/BI** | Full-page dashboard with interactive charts (ApexCharts/Chart.js) and PDF export. |
| **Self-Service Portals** | Simplified, mobile-first form wizards for data updates. |

---

*ScholarCore UI System — aligned with Stitch export `scholarcore_ui_ux_guideline.html`.*
