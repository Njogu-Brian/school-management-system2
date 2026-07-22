# ScholarCore Admin App — Design System V3

> **Status:** Canonical design source of truth + **implemented** in `@erp/ui` / Admin (July 2026)  
> **Platform:** React Native / Expo Admin App (`mobile-app/apps/admin`)  
> **Package:** `@erp/ui` (`mobile-app/packages/ui`)  
> **Phase:** Design kit approved; Stage 1–3 + Visual Overhaul + soft-3D / frosted nav + **Jul 22 operations & UX hardening** — see [IMPLEMENTATION_STATUS.md](./IMPLEMENTATION_STATUS.md)

## Purpose

V3 evolves Design System V2 into a **premium, flagship-quality** design language: one consistent visual system across Finance, Students, Attendance, Staff, Transport, Communication, Reports, Settings, and every other Admin module.

Inspiration comes from the polish, consistency, motion, and usability of leading financial apps — expressed as an **original ScholarCore** language, not a clone of any third-party brand.

## Reading order

| Order | Document | Use when |
|------:|----------|----------|
| 1 | [ANDROID_DESIGN_SYSTEM.md](./ANDROID_DESIGN_SYSTEM.md) | Orienting; principles and architecture |
| 2 | [DESIGN_TOKENS.md](./DESIGN_TOKENS.md) | Implementing theme / token code |
| 3 | [COLOR_SYSTEM.md](./COLOR_SYSTEM.md) | Choosing colors, themes, semantic tones |
| 4 | [TYPOGRAPHY.md](./TYPOGRAPHY.md) | Text styles and hierarchy |
| 5 | [SPACING_SYSTEM.md](./SPACING_SYSTEM.md) | Layout, padding, radius, elevation |
| 6 | [ICON_GUIDELINES.md](./ICON_GUIDELINES.md) | Icons and badges |
| 7 | [ANIMATION_GUIDE.md](./ANIMATION_GUIDE.md) | Motion, transitions, reduced motion |
| 8 | [COMPONENT_LIBRARY.md](./COMPONENT_LIBRARY.md) | Building or extending UI components |
| 9 | [NAVIGATION_GUIDE.md](./NAVIGATION_GUIDE.md) | Drawer, tabs, stacks, sheets |
| 10 | [SCREEN_PATTERNS.md](./SCREEN_PATTERNS.md) | Scaffolding a new screen |
| 11 | [UI_AUDIT.md](./UI_AUDIT.md) | Prioritizing existing screen debt |
| 12 | [UI_AUDIT.md](./UI_AUDIT.md) | Prioritizing existing screen debt |
| 13 | [DESIGN_REVIEW_CHECKLIST.md](./DESIGN_REVIEW_CHECKLIST.md) | Pre-merge gate |
| 14 | [IMPLEMENTATION_STATUS.md](./IMPLEMENTATION_STATUS.md) | Stage 1–3 completion status |

## V2 → V3 status

| Document | Status |
|----------|--------|
| [`docs/ui/admin-app-design-system-v2.md`](../ui/admin-app-design-system-v2.md) | **Superseded** — historical V2 reference |
| [`docs/ui/admin-app-ui-audit.md`](../ui/admin-app-ui-audit.md) | **Superseded** — seed for [UI_AUDIT.md](./UI_AUDIT.md) |
| This folder (`docs/design-system-v3/`) | **Canonical** |

Keep V2 files for migration history. Do not extend them. All new design decisions belong here.

## Implementation home

| Layer | Path |
|-------|------|
| Tokens (runtime) | `mobile-app/packages/ui/src/theme/tokens.ts` — **V3 applied** |
| Theme runtime | `mobile-app/packages/ui/src/theme/ThemeContext.tsx` — motion, opacity, zIndex, AMOLED surfaces, dark semantic |
| Components | `mobile-app/packages/ui/src/` |
| Admin screens | `mobile-app/apps/admin/src/features/` |
| Branding overrides | `mobile-app/apps/admin/src/providers/AppThemeProvider.tsx` (+ `ToastProvider`) |

**Stage 1 foundation:** tokens, theme, Button/TextField/SearchBar, Dialogs/Toast, ScreenHeader, GlobalAppHeader, premium tab chrome, splash `#004A99`, FeedbackProvider, ESLint bans.  
**Stage 2–3 modules + polish:** complete — see [IMPLEMENTATION_STATUS.md](./IMPLEMENTATION_STATUS.md).  
**Verify:** `cd mobile-app && npm run test:design-system`

## What “premium” means here

- Consistent spacing everywhere (almost no cramped UI)
- Large rounded cards (Large **24** / XL **32**; Medium **18** for controls)
- Clear typography hierarchy; single type ramp
- Soft borders and surface steps instead of harsh dividers / heavy shadows
- Charcoal dark mode (not pure black); AMOLED prepared
- Premium blue accent used consistently (`#004A99` ScholarCore primary)
- Branded empty, loading, error, success, and offline states on every screen
- Motion system (150 / 250 / 400 ms) with reduced-motion support
- Custom bottom navigation (floating frosted bar + soft-3D icons — not stock Material)
- Soft-3D clay icons for shortcuts, KPIs, drawer, and tabs (see [ICON_GUIDELINES.md](./ICON_GUIDELINES.md))
- Compact frosted drawer (not a full-width solid white panel)
- Large touch targets (≥ 44–48 dp)
- Every screen feels like one design language

## Out of scope (deferred)

- Custom display font (system fonts per TYPOGRAPHY.md)
- Detox E2E suite on device
- KPI sparklines / tablet master-detail layouts
- Changing navigation IA / API / workflow semantics beyond branded feedback

All new design decisions belong in this folder. Keep V2 docs for migration history only.
