# Blade Partials Migration

Date: 2026-09-09

## Current Blade architecture

The portal has approximately 858 Blade files. It uses:

- `resources/views/layouts/app.blade.php` as the global shell
- Anonymous Blade components under `resources/views/components`
- Module partials under each domain, such as `finance/partials`, `students/partials`, `communication/partials`, and `dashboard/partials`
- Bootstrap/AdminLTE markup with global runtime CSS plus module-specific partial styles
- Many page views that compose local headers, alerts, filters, tables, forms, and embedded JavaScript

Phase 2 introduced generic components for buttons, cards, headers, forms, tables, feedback, navigation, and confirmation modals. Phase 6 retained module business partials and reduced the global dashboard presentation without changing controller data assembly.

## Largest duplication areas

### Repeated feedback and validation

There are hundreds of direct `session('success')`, `session('error')`, `session('warning')`, and `@error` blocks. The main existing variants are:

- `resources/views/partials/alerts.blade.php`
- `resources/views/students/partials/alerts.blade.php`
- `resources/views/finance/invoices/partials/alerts.blade.php`
- `resources/views/dashboard/partials/flash.blade.php`
- `resources/views/communication/partials/flash.blade.php`

The communication variant is not a safe generic replacement because it includes delivery-report links, auto-open behavior, skipped-recipient details, and failed-student-specific workflows.

### Repeated headers

- Finance pages commonly use `finance/partials/header.blade.php`.
- Communication pages use `communication/partials/header.blade.php`.
- Settings-style pages repeat `page-header` markup and settings styles.
- Academic pages commonly repeat an eyebrow, title, description, back link, and action group.

Finance and communication header partials are already safe candidates for gradual migration to `x-page-header`; their module-specific context should remain in the partial.

### Repeated filters and action groups

Filters and action bars recur across students, finance, academics, HR, transport, attendance, and reports. Phase 2 added `x-data.filter-bar`, `x-data.actions`, and `x-nav.page-actions`, but adoption is limited to pilots. Filters with domain-specific query behavior should become module partials that compose those components.

### Repeated tables and rows

Plain responsive tables occur throughout finance, student lists, attendance, academic reports, HR, inventory, and communication logs. Generic table structure belongs in `x-data.table`; domain-specific headings, row actions, status rules, and empty states belong in module partials.

### Repeated student sections

Student identity, guardian/family details, academic placement, attendance summaries, documents, and financial summaries appear across student details, parent/family pages, admissions, reports, and communication selectors. These are business partial candidates, not generic components. They must preserve context-specific permissions and loaded relationships.

### Repeated finance sections

Finance summaries, fee balances, invoice/payment rows, receipt details, statement lines, fee filters, and posting summaries are repeated across billing and accounting pages. Financial calculations must remain in controllers/services; partials should only format already-authorized view data.

### Styles and large files

Many views include local `<style>` blocks. High-risk or specialized style areas include PDF/print templates, finance public payment/receipt views, exam report print layouts, communication selector modals, and transport/route screens. These should not be bulk-migrated.

Large/high-density areas include the admin dashboard, student form/details, finance bank statements, expense statement analyzer, timetables, exam report sheets, and communication forms. Large size alone is not proof of duplication; stateful workflows must be split only after contract mapping.

## Components versus partials

### Use components for

- Buttons, icon buttons, cards, page headers
- Inputs, selects, textareas, labels, errors
- Tables and generic action groups
- Alerts, flash messages, empty/loading states
- Confirmation modals, breadcrumbs, tabs, pagination

### Use partials for

- Student identity and guardian sections
- Finance summary and transaction blocks
- Attendance rows and filters
- Module headers and action groups
- Exam matrix rows and audit panels
- Communication recipient selectors and channel forms
- Domain-specific table rows and workflow panels
- Navigation subsections

Do not create a component for a business object merely because it renders markup. Do not create a one-line partial that hides no meaningful contract.

## Safe migration implemented in this phase

The generic alert partials now delegate to the Phase 2 feedback components:

- `resources/views/partials/alerts.blade.php`
- `resources/views/students/partials/alerts.blade.php`
- `resources/views/finance/invoices/partials/alerts.blade.php`
- `resources/views/dashboard/partials/flash.blade.php`

Their existing session keys, error-bag behavior, dismissibility, and special failed-student content are preserved. Communication flash remains specialized and intentionally untouched.

The existing module headers were also migrated to the shared page-header component:

- `resources/views/finance/partials/header.blade.php`
- `resources/views/communication/partials/header.blade.php`

Stable contract preserved:

- `title`: displayable page title
- `icon`: Bootstrap icon class
- `subtitle`: optional page description
- `actions`: existing trusted action HTML supplied by the caller

The finance header retains its `finance-hero` class and finance style include. The communication header retains its Communication eyebrow. All existing include sites remain unchanged.

## Safe migration candidates

1. Generic alert partials, completed in this phase.
2. Finance and communication page headers, using `x-page-header` while retaining module-specific action contracts.
3. Student list/admission filters as module partials composed from `x-data.filter-bar` and form components.
4. Repeated academic report filter panels.
5. Shared finance invoice/payment table row partials after comparing columns and action permissions.
6. Attendance report filter and status-row partials.
7. Communication recipient-scope selector partial shared by SMS, email, WhatsApp, and Compose.

## Risky migration candidates

- Student admission and family/guardian sections: tightly coupled validation, sibling linking, uploads, and old-input behavior.
- Finance statement analyzer and bank statements: parsing state, mutations, approval, reversals, and provider/payment relationships.
- Exam marks matrices: calculations, permissions, autosave, draft state, and submit-for-review behavior.
- Timetable generation/editor views: feasibility, locking, regeneration, substitutions, and publishing.
- PDF/print templates: layout is format-specific and often intentionally uses embedded styles.
- Communication channel forms: shared recipient fields but provider-specific validation, uploads, queueing, rate limits, and progress flows.

## Proposed migration order

1. Generic feedback partials and component adoption.
2. Module headers and action groups.
3. Stable read-only filters and table rows.
4. Student and attendance summary sections.
5. Finance billing summaries and read-only tables.
6. Communication recipient selector after all channel contracts are mapped.
7. Stateful academic/finance workflow sections only with focused feature tests.
8. Embedded style migration last, module by module.

## Phase 6.6 contract review

Finance invoice and payment filters were compared and intentionally kept separate. Invoice filters use academic year/term, votehead, class, stream, student, status, and orphan controls. Payment filters use student, class, stream, payment method, allocation status, sorting, receipt/transaction search, and date range.

Invoice and payment tables were also kept separate. Invoice rows include invoice-item discount calculations, balances, academic context, exports, and invoice-specific bulk actions. Payment rows include allocation state, reversal state, cross-term coverage, receipt printing, and communication actions. A shared generic table component is appropriate; a shared business row partial is not yet safe.

The communication recipient selector remains a future candidate. SMS, email, and WhatsApp share recipient concepts but have different field IDs, custom recipient names, uploads/media, scheduling, and embedded scripts. The existing forms are already reused by the Compose shell; extracting the selector requires first separating its JavaScript contract from provider behavior.

Student read-only sections, attendance filters/rows, and academic report filters remain documented candidates. Their current relationship loading, permissions, and input names are not sufficiently uniform for a safe forced extraction in this phase.

## Remaining style debt

This phase does not mass-delete inline styles. Remaining style debt includes settings/dashboard/finance partial styles, communication selector styles, academic report print CSS, receipt/PDF styles, and many page-local blocks. Only styles directly owned by a future extracted partial should move during that partial's migration.
