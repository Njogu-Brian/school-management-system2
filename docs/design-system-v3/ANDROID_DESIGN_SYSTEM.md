# Android Design System — ScholarCore Admin V3

> Master design language for the **React Native / Expo Admin App** (`mobile-app/apps/admin`).  
> Filename kept as `ANDROID_DESIGN_SYSTEM.md` per initiative naming; implementation is RN + `@erp/ui`, not Android XML/Compose.

## 1. Vision

Every screen follows **one design language**. Nothing feels random. The Admin App should feel as cohesive and deliberate as a flagship banking product: consistent spacing, large rounded surfaces, clear hierarchy, soft depth, branded feedback, and purposeful motion — while remaining an **original ScholarCore** expression (brand primary `#004A99`), not a copy of any third-party app.

## 2. Principles

| # | Principle | Meaning |
|---|-----------|---------|
| 1 | **Consistency first** | Same radius, type, padding, and components everywhere |
| 2 | **One focal point** | Each viewport region has one job and one primary message |
| 3 | **Breathing room** | Prefer generous section gaps over dense ERP walls |
| 4 | **Soft elevation** | Surface steps + subtle borders beat heavy shadows |
| 5 | **Semantic color** | Status always maps to success/warning/danger/info/brand |
| 6 | **States are product** | Loading, empty, error, offline, success are first-class |
| 7 | **Motion with purpose** | Animate hierarchy and feedback, not decoration |
| 8 | **Accessible by default** | 44–48dp targets, AA contrast, reduced motion, font scaling |
| 9 | **Tokens only** | No hardcoded colors, spacing, or radii in feature code |
| 10 | **Reusable before unique** | New UI starts from [COMPONENT_LIBRARY.md](./COMPONENT_LIBRARY.md) / [SCREEN_PATTERNS.md](./SCREEN_PATTERNS.md) |

## 3. Architecture

```
Design Tokens  →  ThemeProvider / useTheme()  →  Components (@erp/ui)
                                                      ↓
                                              Screen Patterns
                                                      ↓
                                              Feature Screens
```

| Layer | Responsibility | Doc |
|-------|----------------|-----|
| Tokens | Values only | [DESIGN_TOKENS.md](./DESIGN_TOKENS.md) |
| Color / Type / Space | Atomic rules | COLOR / TYPOGRAPHY / SPACING |
| Components | Anatomy + variants | [COMPONENT_LIBRARY.md](./COMPONENT_LIBRARY.md) |
| Patterns | Screen templates | [SCREEN_PATTERNS.md](./SCREEN_PATTERNS.md) |
| Navigation | Chrome + transitions | [NAVIGATION_GUIDE.md](./NAVIGATION_GUIDE.md) |
| Motion | Timing + recipes | [ANIMATION_GUIDE.md](./ANIMATION_GUIDE.md) |
| Audit | Current debt | [UI_AUDIT.md](./UI_AUDIT.md) |
| Gate | PR checklist | [DESIGN_REVIEW_CHECKLIST.md](./DESIGN_REVIEW_CHECKLIST.md) |

## 4. Visual language summary

| Element | V3 rule |
|---------|---------|
| Brand accent | Premium blue `#004A99` (elevated on dark for controls) |
| Background (dark) | Charcoal navy `#0a1628` — not pure black (unless AMOLED) |
| Cards | `radius.card` **24**, soft `borderSubtle`, elevation 1–2 |
| Controls | `radius.control` **18**, min height 48 |
| Sheets | Top radius **28**, overlay surface |
| Type | Single ramp; KPI values = `headline`; labels = `overline` |
| Spacing | 4-based scale only; screen pad 16; premium card pad 20 |
| Icons | Ionicons outlined/filled; optional tinted wells; no emoji icons |
| Nav | Custom bottom tabs + drawer; active primary accent |
| Empty / success | Illustrated branded states — never plain gray text alone |

## 5. Theme modes

| Mode | Requirement |
|------|-------------|
| Light | Default; cool gray-blue canvas |
| Dark | Charcoal surfaces; verified contrast |
| AMOLED | Prepared tokens; Settings opt-in later |
| School / Corporate | Primary + logo via `BrandingProvider` |

## 6. Module coverage

Design language applies equally to:

Auth · Dashboard · Students · Finance · People/HR · Approvals · Admissions · Academics · Operations (Transport, Inventory, Library, Visitors, Assets) · Communication · Reports · Settings · Profile · Notifications · Search · Activity · Diagnostics

No module is exempt from tokens, empty states, or review checklist.

## 7. Relationship to V2

V2 delivered semantic colors, a type scale, 4pt spacing, shared SearchBar/FilterChip/StatusBadge, and module heroes. Adoption was **patchy**.

V3:

- Raises radius and motion to premium bar
- Completes component gaps (dialogs, toast, custom bottom nav chrome, unified 360 tabs)
- Mandates states on every screen
- Supersedes V2 docs; runtime migrates in Stage 1 after approval

## 8. Non-goals (Phase 1 documentation)

- No screen rewrites
- No API or information-architecture changes
- No cloning competitor brand assets or literal layouts

## 9. Post-approval roadmap (reference only)

| Stage | Focus |
|-------|--------|
| **1 — Foundation** | Tokens, theme, primitives, motion, dialogs, nav chrome |
| **2 — Modules** | Dashboard → Finance → Students → Attendance/Academics → Staff → Transport → Communication → Reports → Settings |
| **3 — Polish** | Pixel review, a11y, performance 60fps, refactor leftovers |

## 10. Definition of done (for a screen)

A screen is V3-complete when:

- [ ] Uses only design tokens
- [ ] Uses shared components / patterns (no one-off chrome)
- [ ] Has loading (skeleton), empty, error, offline handling
- [ ] Meets [DESIGN_REVIEW_CHECKLIST.md](./DESIGN_REVIEW_CHECKLIST.md)
- [ ] Feels like the same app as Dashboard and Finance hubs
