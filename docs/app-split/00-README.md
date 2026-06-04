# School ERP — Mobile App Split: Product Architecture Deliverables

> **Role:** Senior Product Architect / Mobile App Auditor / UX Architect / RN Enterprise Consultant
> **Objective:** Split one mixed-role React Native (Expo) app into two focused apps — **Staff App** (existing app, stripped of admin functions) and a **new Admin App** — both consuming the same Laravel APIs.
> **Status:** Architecture & design phase. **No production code written yet** (by design).

---

## How this set of documents is organized

| # | Document | What it answers |
|---|----------|-----------------|
| 1 | [`01-codebase-audit.md`](./01-codebase-audit.md) | What exists today: screens, navigation, APIs, roles, permissions, components, services, notifications, background tasks, state, auth. |
| 2 | [`02-feature-matrix.md`](./02-feature-matrix.md) | For every feature: Staff-only / Admin-only / Shared — with reasons. |
| 3 | [`03-gap-analysis.md`](./03-gap-analysis.md) | Missing modern features across Academics, Communication, Transport, Finance, Administration, HR. |
| 4 | [`04-architecture.md`](./04-architecture.md) | Target architecture: app structure, folders, feature modules, shared library, API layer, auth/offline/push/analytics/error strategies. |
| 5 | [`05-app-designs.md`](./05-app-designs.md) | Sitemaps, navigation, bottom tabs, drawers, dashboards, journeys for both apps. |
| 6 | [`06-ui-specifications.md`](./06-ui-specifications.md) | High-fidelity per-screen specs (Stitch/Figma ready): purpose, components, actions, API calls, empty/loading/error states. |
| 7 | [`07-implementation-roadmap.md`](./07-implementation-roadmap.md) | Phased delivery plan → Play Store production release. |

---

## Executive summary

### What we found
- **Platform:** Expo SDK 54, React Native 0.81, React 19, TypeScript. React Navigation v6 (bottom-tabs + stack). **No Redux/Zustand/React Query** — state is React Context + local `useState` + `react-hook-form`.
- **Scale:** ~193 source files, **84 screens**, **24 navigators**, **23 API modules (~180 unique endpoints)**, **14 distinct user roles**, **20 shared components**.
- **Role routing today:** A single `RoleBasedNavigator` branches on `user.role` into six completely different navigation shells (Admin tabs, Academic-Admin tabs, Teacher stack, Parent tabs, Student tabs, Driver tabs). **The split is already latent in the code** — admin shells and staff shells barely share screens beyond a handful (`StudentsListScreen`, `StudentDetailScreen`, `ReportCardScreen`, `SettingsScreen`).
- **Branding:** Server-driven per-school theming via `GET /app-branding` (`mergePortalColors`) — a strong foundation for a two-app, multi-tenant ecosystem.
- **Notable gaps:** No real offline sync (despite README claims), push is register-only (no in-app routing/deep links), no analytics layer, no chat/messaging UI, no live bus tracking, no background tasks, `AdminBrandedProvider` is defined but never wired.

### The core recommendation
The cleanest path is **not** to fork the repo twice. Convert the existing repo into a **monorepo / shared-core architecture**: one shared package (`@erp/core`: API layer, auth, theming, design system, types) consumed by two thin app shells (`apps/staff`, `apps/admin`). This:
- Lets the existing app become the **Staff App** by deleting admin navigators and admin-only screens.
- Lets the **Admin App** reuse the entire API layer, auth, branding and component library from day one.
- Avoids divergence of the Laravel API contract across two codebases.

### Role → App allocation (summary)

| Staff App | Admin App |
|-----------|-----------|
| Teacher, Senior Teacher, Class Teacher, Subject Teacher, Supervisor, Parent, Guardian, Student, Driver, Transport, Nurse*, Librarian* (self-service), Store Keeper* (requests) | Super Admin, Admin, Secretary, Principal, Deputy Principal, Head Teacher, Academic Admin, Accountant, Finance, Bursar, Receptionist, Librarian (management), Nurse (management), Store Keeper (management), Security |

\* Operational, single-desk roles (Nurse, Librarian, Store Keeper, Security) are split by **intent**: self-service/data-capture lives in Staff App; full management/configuration lives in Admin App. See [feature matrix](./02-feature-matrix.md) for the reasoning.

> Note: several roles in the original brief (Principal, Deputy Principal, Head Teacher, Bursar, Receptionist, Nurse, Store Keeper, Security) **do not yet exist as distinct roles** in the mobile codebase (`src/constants/roles.ts` defines 14 roles). The backend may expose more. Gap-closing for these roles is covered in the roadmap.
