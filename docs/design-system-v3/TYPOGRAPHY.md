# Typography — Design System V3

> Single type ramp for ScholarCore Admin. Deprecate dual usage of `typography.*` and legacy `fontSizes.*`.

## Principles

1. **One ramp** — every screen uses named roles below; no ad-hoc `fontSize: 17`.
2. **Hierarchy over decoration** — weight + size carry meaning; avoid all-caps walls of text.
3. **Readable on phone** — body ≥ 15; captions ≥ 13; minimum touch-adjacent labels remain legible at 200% font scale.
4. **System / platform font** — React Native default sans (San Francisco / Roboto). Custom display font only if product branding later mandates it; do not introduce Inter/Roboto as a web-style override without design approval.
5. **Letter spacing** — tighten display/heading slightly; open overlines for scanability.

## Type scale

| Role | Size | Line height | Weight | Letter spacing | Usage |
|------|-----:|------------:|--------|---------------:|-------|
| `displayLarge` | 34 | 40 | 700 | −0.6 | Rare marketing / onboarding hero |
| `display` | 28 | 34 | 700 | −0.5 | Onboarding titles, empty-state headlines |
| `headlineLarge` | 24 | 30 | 700 | −0.4 | Module hero titles (optional large variant) |
| `headline` | 22 | 28 | 700 | −0.3 | Hero titles, KPI primary values |
| `title` | 18 | 24 | 600 | 0 | Section headers, sheet titles, screen titles |
| `titleSmall` | 16 | 22 | 600 | 0 | Card titles, list primary (dense) |
| `subtitle` | 15 | 22 | 500 | 0 | Supporting line under titles |
| `bodyLarge` | 16 | 24 | 400 | 0 | Long-form readable body |
| `body` | 15 | 22 | 400 | 0 | Default list names, form values |
| `bodyMedium` | 15 | 22 | 500 | 0 | Emphasized body, selected rows |
| `button` | 15 | 20 | 600 | 0.2 | Primary / secondary button labels |
| `label` | 13 | 18 | 600 | 0.2 | Form field labels above inputs |
| `caption` | 13 | 18 | 500 | 0.1 | Meta, timestamps, tab labels |
| `overline` | 11 | 14 | 600 | 0.6 | KPI labels, filter section labels (sentence case preferred; uppercase only for true overlines) |
| `tiny` | 10 | 12 | 500 | 0.4 | Badge micro-copy only (“NEW”); avoid elsewhere |

### V2 compatibility aliases

| V2 token | V3 role |
|----------|---------|
| `typography.display` | `display` |
| `typography.heading` | `headline` |
| `typography.title` | `title` |
| `typography.body` | `body` |
| `typography.bodyMedium` | `bodyMedium` |
| `typography.caption` | `caption` |
| `typography.overline` | `overline` |

## Legacy `fontSizes` — migration

| Legacy | Do not use for | Replace with |
|--------|----------------|--------------|
| `fontSizes.xs` (12) | Labels | `caption` or `overline` |
| `fontSizes.sm` (14) | Body | `body` (15) or `caption` (13) |
| `fontSizes.md` (16) | Titles | `titleSmall` / `bodyLarge` |
| `fontSizes.lg` (18) | Headers | `title` |
| `fontSizes.xl` (20) | — | `headline` or `title` |
| `fontSizes.xxl` (24) | — | `headlineLarge` |
| `fontSizes.xxxl` (32) | — | `display` / `displayLarge` |

Stage 1: keep `FONT_SIZES` export for compile compatibility; Stage 2 screens must not import it. ESLint rule (recommended): ban `fontSizes` in `apps/admin`.

## Color pairing

| Role | Default color token |
|------|---------------------|
| Display / Headline / Title | `textMain` |
| Subtitle / Body | `textMain` or `textSub` for secondary |
| Caption / Overline / Tiny | `textSub` or `textMuted` |
| Button on primary fill | `textOnPrimary` |
| Links | `textLink` |

## Hierarchy patterns

### Module hub

```
headline     Module title (or hero)
body / sub   One supporting sentence
overline     KPI labels
headline     KPI values
title        Section headers
body         List row titles
caption      List meta
```

### Registry list row

```
bodyMedium   Primary name
caption      Meta line (class · status)
overline     Optional section label above list
```

### Form

```
label        Field label
body         Input value / placeholder (placeholder → textMuted)
caption      Helper / error (error → semantic.danger.fg)
```

## Accessibility

- Support Dynamic Type / font scaling; prefer role tokens over fixed pixel layouts that clip.
- Do not use color alone for meaning; pair status color with label text.
- Minimum contrast: body on `surface` / `background` AA.
- Tab and button labels: `caption` or `button` — not `tiny`.

## Do / Don't

| Do | Don't |
|----|-------|
| Use `theme.typography.<role>` | Mix `fontSizes` and `typography` on one screen |
| One clear focal text block per viewport region | Multiple competing 22px headlines |
| Sentence case for UI | ALL CAPS paragraphs |
| Truncate long titles with ellipsis + full string in a11y | Shrink font until unreadable |
