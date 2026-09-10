# Web Portal and Mobile Apps UX Assessment

Date: 2026-09-09

## Scope

This assessment separates the products that were previously blended together:

- **Web portal:** Laravel Blade, Bootstrap 5/AdminLTE conventions, used primarily by administrators, finance, HR, academic, transport, and other operational staff.
- **Mobile users app:** React Native Expo app at `mobile-app/apps/users`, used by teachers, parents, students, drivers, and other non-admin staff.
- **Mobile admin app:** React Native Expo app at `mobile-app/apps/admin`, intended for school-management and RBAC administration.

The report evaluates the implemented structure and interaction patterns. It is not a visual screenshot audit; emulator/device review should be the next validation step for the mobile products.

## Executive Summary

The web portal and mobile app are solving different jobs and should not be forced into the same information architecture.

The **web portal is operationally powerful but overloaded**. It contains roughly 1,000 route declarations and about 830 Blade views across finance, academics, HR/payroll, students, communication, transport, website CMS, reports, and settings. Its largest UX risk is discoverability: too many destinations, overlapping workflows, dense dashboards, and long single-page data-entry surfaces.

The **users mobile app has a stronger product foundation**. It uses one role-adaptive binary, dedicated shells for parent, teacher, student, and driver roles, shared UI primitives, centralized theme tokens, loading/empty/error states, offline attendance drafts, and secure PIN/biometric re-entry. Its largest UX risks are not basic structure; they are task depth, accessibility details, reliability feedback, and lack of feature-level regression coverage.

The recommended strategy is therefore:

1. Simplify the web portal around work areas and high-frequency tasks.
2. Preserve the mobile app's role-based model and make its highest-risk workflows more resilient.
3. Establish shared product language across both surfaces without making their navigation identical.
4. Test the critical workflows on real devices and representative portal resolutions before broad visual redesign.

## Product Comparison

| Dimension | Web portal | Mobile users app |
|---|---|---|
| Primary users | Admin and operational staff | Teachers, parents, students, drivers |
| Core strength | Depth, reporting, finance and administration | Focused role workflows and mobility |
| Main navigation risk | Too many modules and nested destinations | Important actions can be buried across tabs/stacks |
| Main form risk | Long, single-page forms | Multi-step flows may require too much navigation |
| Feedback model | Session alerts, spinners, native browser confirmations | Shared toast and confirmation bridge, screen-level loading states |
| Data density | High and often appropriate for desktop operations | Lower density, better suited to quick decisions |
| Offline behavior | Not a central interaction model | Attendance supports saved drafts and queued execution |
| Accessibility risk | Keyboard flow, skip navigation, complex dashboard semantics | Color-only status cues and limited evidence of screen-reader testing |
| Testing risk | No visible end-to-end or accessibility suite | Only shared UI tests found; no app feature test files found |

## Web Portal Findings

### Strengths

- Broad role coverage and substantial operational depth.
- Responsive Bootstrap foundation and shared layout shell.
- Inline form validation is established through `@error`, `is-invalid`, and `invalid-feedback` patterns.
- Most list views use pagination, responsive table wrappers, filters, and empty states.
- Role-specific dashboards exist for admin, finance, teacher, supervisor, parent, student, and transport users.
- Brand/theme variables already exist in the application layout, including dark-theme variables.

### P0: Student admission is a single large data-entry surface

The student form contains approximately 72 fields covering identity, family, medical, special needs, transport, KEMIS, and related records. A single page makes the task feel like a database form rather than a guided admission process.

**Impact:** high abandonment and correction cost, poor progress visibility, and increased likelihood of incomplete or inconsistent records.

**Recommendation:** introduce a saveable 4-step flow:

1. Student identity and enrollment
2. Family and guardians
3. Health, special needs, and documents
4. Transport, review, and submit

Keep a persistent draft and show section-level completion rather than hiding all validation until final submission.

### P1: Navigation scale is the core discoverability problem

The portal spans approximately 1,000 route declarations and more than 20 major functional areas. Finance alone contains many overlapping concepts: student billing, accounting, expenses, imports, payments, statements, and payment integrations.

**Impact:** staff depend on memory, menu hunting, or browser history; new users cannot form a reliable mental model.

**Recommendation:** organize the sidebar around jobs and audiences, not every backend resource:

- Dashboard
- Students and families
- Teaching and learning
- Student billing
- Accounting and expenses
- People and payroll
- Communication
- Transport and activities
- Reports
- Administration

Add a searchable command/menu entry point for destinations and recent/frequent actions.

### P1: Dashboards prioritize coverage over decisions

The admin dashboard contains many widgets and chart sections. This creates a long scanning task before a user reaches the action that brought them there.

**Recommendation:** above the fold, show four or fewer decision KPIs, urgent alerts, and a short list of next actions. Move analytical widgets to module dashboards and allow role-specific configuration later.

### P1: Feedback is inconsistent and sometimes browser-native

The web codebase uses session alerts and native `confirm()`/`alert()` patterns alongside spinners. These behaviors vary by browser and do not communicate background work consistently.

**Recommendation:** standardize three primitives:

- Toasts for completed background actions
- Branded confirmation dialogs for destructive actions
- Button-level busy states with duplicate-submit protection

### P1: Desktop-first density needs task-specific responsive behavior

Responsive table wrappers and breakpoint rules exist, but hiding columns is not the same as designing a mobile workflow. Large tables, 200-row pages, long forms, and PDF-heavy actions remain difficult on smaller screens.

**Recommendation:** define a mobile behavior per high-use list: compact card rows, server-side filters, progressive disclosure, and explicit bulk-action limits. Do not attempt to make every administrative screen equivalent to the native app.

### P1/P2: Accessibility needs a formal pass

Labels and some ARIA attributes are present, but there is no visible skip link, broad keyboard-navigation test coverage, or semantic alternative for chart-heavy content.

**Recommendation:** add skip navigation, visible focus states, keyboard tests for menus/modals/forms, accessible names for icon actions, and textual summaries for important chart data.

## Mobile Users App Findings

### Strengths

- One binary adapts to the current role through `RoleBasedNavigator`.
- Parent, teacher, student, and driver experiences have distinct navigation shells.
- Dual-identity users can switch between work and home modes.
- Parent navigation uses clear domain tabs: Home, Children, Fees, Academic, and More.
- Shared UI package provides tokens, typography, spacing, elevation, themed surfaces, buttons, fields, empty states, skeleton rows, toasts, and confirmation dialogs.
- Attendance includes school-day checks, loading states, saved offline drafts, and queued execution support.
- Authentication supports password/OTP/Google, PIN unlock, biometrics, and remembered username.

### P0: Critical feature workflows lack feature-level test coverage

The repository contains design-system tests under `mobile-app/packages/ui/src/__tests__`, but the feature-test search found no app-level `test` or `spec` files under the users or admin applications.

**Impact:** attendance, marks, payments, transport actions, and parent communication can regress without a fast automated signal.

**Recommendation:** begin with behavior tests for:

1. Teacher attendance: load, draft, offline fallback, submit, and conflict behavior
2. Teacher marks: sheet and matrix entry, validation, save, and retry
3. Parent fees: statement loading, M-Pesa prompt, success, cancellation, and timeout
4. Parent diary/chat: send, attachment failure, retry, and refresh
5. Driver trip workflow: active trip, boarding, location failure, and completion

### P1: Attendance status is still color-dependent

`MarkAttendanceScreen` renders attendance choices as `P`, `A`, and `L`, with green, red, and amber backgrounds. The button has an accessibility role and selected state, but the visible status words are not present.

**Impact:** the meaning is less clear for new users and is weaker for color-blind users and some screen-reader contexts.

**Recommendation:** use full visible labels such as `Present`, `Absent`, and `Late`, or pair the abbreviation with an accessible label and a persistent legend. Keep the color as reinforcement, never as the only semantic cue.

### P1: Role adaptation is coherent but can hide cross-role context

The role-based model reduces irrelevant navigation, and the work/home mode is a good answer for dual-identity users. However, a teacher who is also a parent must understand which mode is active and how to switch without losing work in progress.

**Recommendation:** make the current mode explicit in the header/account surface, preserve unsaved drafts when switching, and include a clear destination after switching. Add analytics for failed or repeated mode switches.

### P1: Mobile task flows need stronger recovery language

The app has centralized feedback and many explicit loading/error/empty states. Several operations still surface generic messages such as upload or load failures, with retry behavior varying by screen.

**Recommendation:** use a consistent error model:

- What failed
- Whether local work was preserved
- The next action: Retry, Continue offline, or Contact school
- A stable retry control near the failed content

For payments, location, file uploads, and attendance synchronization, expose pending and confirmed states separately.

### P1: Deep stacks and repeated shared screens can increase navigation friction

The parent shell has five tabs with repeated shared destinations such as notifications, settings, wallet, concerns, student details, statements, and payment details. This is structurally flexible but can make back behavior and deep links harder to predict.

**Recommendation:** test every deep link from notification, push message, and home quick action. Define a single back-stack policy for cross-tab navigation and show a consistent breadcrumb/context title for child-specific screens.

### P2: Notification and status visibility can improve

The app has notifications screens and push support, but the tab configuration does not show a clear unread-count strategy in the inspected navigation. Important parent, teacher, and driver actions may therefore require manual checking.

**Recommendation:** add badges only where they represent actionable unread work, and distinguish informational announcements from tasks requiring a response.

### P2: Mobile design system needs usage documentation

The shared token system is a strong foundation, including light, dark, and AMOLED surfaces, semantic colors, spacing, typography, radius, elevation, and motion. There is no visible Storybook-style catalog or usage guide.

**Recommendation:** document component states and accessibility requirements in the shared UI package. A lightweight screen gallery is sufficient; a full Storybook deployment is optional.

## Cross-Product Experience Gaps

### Shared language, different navigation

The web portal and mobile app should share names for concepts such as Attendance, Fees, Diary, Results, Transport, and Concerns. They should not share identical menus: web is optimized for administration and bulk work; mobile is optimized for role-specific, time-sensitive tasks.

### Shared account and notification model

Users should receive consistent naming, status vocabulary, and notification meaning across portal and mobile. A parent should not see one payment state on mobile and another label in the portal.

### Shared action states

Define the same lifecycle everywhere:

`Not started -> Draft -> Pending sync/payment -> Completed -> Failed with retry`

This is especially important for attendance, payments, uploads, and communication.

### Shared accessibility standard

Create one product checklist covering focus/keyboard behavior for web and accessible labels, contrast, dynamic type, and non-color semantics for mobile.

## Prioritized Roadmap

### Phase 1: Reduce risk

- Add mobile feature tests for attendance, marks, payments, diary, and driver trip completion.
- Replace `P/A/L` as the only visible attendance meaning.
- Add retry and preserved-draft language to mobile error states.
- Add web skip link, focus states, and standardized confirmation/toast primitives.

### Phase 2: Improve discoverability

- Add searchable web navigation and reorganize finance into job-based areas.
- Reduce the admin dashboard to decisions, alerts, and next actions.
- Make mobile work/home mode visibly explicit.
- Add unread badges for actionable mobile notifications.

### Phase 3: Improve completion rates

- Split the web admission form into saveable steps.
- Refactor other high-risk bulk entry screens around a shared matrix/sheet pattern.
- Define mobile deep-link and back-stack behavior.
- Publish shared UI and content-language guidance.

### Phase 4: Validate in context

- Run web keyboard and screen-reader checks at desktop and laptop widths.
- Run Android/iOS tests on low-memory and poor-network conditions.
- Measure completion time and error rate for admission, attendance, marks, payment, and transport tasks.
- Use those measurements to decide which visual refinements are worth shipping.

## Recommended First Implementation Slice

The best first slice is not a global visual rewrite. Implement one complete vertical workflow in each product:

- **Web:** student admission wizard with draft save, step validation, review, and accessible confirmation.
- **Mobile:** teacher attendance with full status labels, offline draft recovery, retry states, and feature tests.

These two slices target the highest-risk data-entry paths and establish reusable patterns for forms, feedback, accessibility, and testing.
