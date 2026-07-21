# Color System — Design System V3

> Canonical color roles for ScholarCore Admin App. Runtime today: [`tokens.ts`](../../mobile-app/packages/ui/src/theme/tokens.ts). V3 values below are **targets** for Stage 1.

## Principles

1. **Semantic first** — never hardcode hex in screens; use `theme.palette` / `theme.semantic`.
2. **One primary accent** — ScholarCore blue `#004A99` for actions, active states, links.
3. **Soft dark mode** — charcoal surfaces (`#0c1018` family), not pure `#000000` (AMOLED uses `#000000` when enabled).
4. **Contrast** — body text ≥ 4.5:1 against surfaces (WCAG AA); large text ≥ 3:1.
5. **Branding hooks** — `BrandingProvider` may override primary; semantic success/warning/danger stay stable.

## Brand

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `primary` | `#004A99` | `#4B9FFF` (elevated for contrast) | CTAs, active tabs, links, chart lines |
| `primaryDark` | `#003366` | `#003366` | Hero gradient start (dark) |
| `primaryLight` | `#1a6bc4` | `#6BB0FF` | Hero gradient end, pressed accents |
| `primaryMuted` | `#e8f1fb` | `#1a2f4a` | Icon wells, selected chip fill |
| `secondary` | `#14b8a6` | `#2dd4bf` | Secondary accent (rare; finance highlights) |

**Note:** V2 uses the same light primary on dark UI. V3 **raises** interactive primary on dark surfaces for readability while keeping brand identity on heroes/logos.

### Splash / native chrome

`app.config.ts` splash / primaryColor aligned to `#004A99`.

## Semantic tones

Use for badges, alerts, status pills. Access: `theme.semantic.<tone>.{fg,bg,border}`.

| Tone | FG Light | BG Light | FG Dark | BG Dark | Use |
|------|----------|----------|---------|---------|-----|
| `brand` | `#004A99` | `#e8f1fb` | `#4B9FFF` | `#1a2f4a` | Neutral brand status |
| `success` | `#059669` | `#d1fae5` | `#34d399` | `#064e3b` | Paid, present, approved |
| `warning` | `#d97706` | `#fef3c7` | `#fbbf24` | `#78350f` | Pending, overdue soft |
| `danger` | `#dc2626` | `#fee2e2` | `#f87171` | `#7f1d1d` | Failed, rejected, critical |
| `info` | `#2563eb` | `#dbeafe` | `#60a5fa` | `#1e3a8a` | Informational |

Never invent a sixth tone for one screen — map domain statuses onto these five.

## Surfaces

| Token | Light | Dark | AMOLED | Usage |
|-------|-------|------|--------|-------|
| `background` | `#eef2f7` | `#0c1018` | `#000000` | Screen canvas |
| `surface` | `#ffffff` | `#151a24` | `#0a0a0a` | Default cards |
| `surfaceRaised` | `#ffffff` | `#1c2330` | `#121212` | Elevated cards, search, inputs |
| `surfaceMuted` | `#e8edf4` | `#222938` | `#1a1a1a` | Tab tracks, empty icon wells |
| `surfaceOverlay` | `rgba(255,255,255,0.96)` | `rgba(21,26,36,0.96)` | `rgba(18,18,18,0.98)` | Bottom sheets, dialogs |
| `surfaceInverse` | `#0f172a` | `#f8fafc` | `#f8fafc` | Rare inverse chips |

**Card elevation without heavy shadow:** prefer `surfaceRaised` + `borderSubtle` over large drop shadows.

## Text

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `textMain` | `#0b1220` | `#f4f7fb` | Titles, primary body |
| `textSub` | `#5b6b7c` | `#9aa8b8` | Secondary body, meta |
| `textMuted` | `#8b9aab` | `#6b7a8c` | Placeholders, disabled hints |
| `textOnPrimary` | `#ffffff` | `#ffffff` | Text on primary buttons |
| `textLink` | `primary` | `primary` (dark elevated) | Inline links |

## Borders & disabled

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `border` | `#d8e0ea` | `#2a3444` | Input borders, strong separators |
| `borderSubtle` | `#e8eef5` | `#1f2836` | Card outlines, soft dividers |
| `disabled` | `#cbd5e1` | `#475569` | Disabled controls / icons |
| `disabledBg` | `#f1f5f9` | `#1e293b` | Disabled button fill |

**Rule:** Prefer soft `borderSubtle` over full-width harsh hairline dividers inside cards. Within grouped lists, dividers stop before leading icons and trailing chevrons.

## Theme modes

| Mode | Status | Notes |
|------|--------|-------|
| **Light** | Required | Default |
| **Dark** | Required | Charcoal navy — already partially in tokens |
| **AMOLED** | Prepared | True black canvas; Stage 1 adds palette keys, Stage 2 adopts |
| **School theme** | Hook | Override `primary*` via branding API |
| **Corporate theme** | Hook | Same branding path; document school logo + primary |

`ThemeProvider` today: light / dark / auto. V3 adds AMOLED as an explicit palette variant selectable from Settings (Stage 1+).

## Charts & data viz

| Role | Token source |
|------|----------------|
| Primary series | `palette.primary` |
| Secondary series | `palette.secondary` |
| Positive | `semantic.success.fg` |
| Negative | `semantic.danger.fg` |
| Grid / axis | `palette.borderSubtle` / `textMuted` |
| Chart card fill | `surfaceRaised` |

No raw `rgba(0,74,153)` literals in chart components.

## Do / Don't

| Do | Don't |
|----|-------|
| Use `useTheme().palette` and `semantic` | Hardcode `#E8F0FA`, `#ccc`, chart rgba |
| Map domain status → semantic tone | One-off hex maps per badge file |
| Keep dark backgrounds in charcoal family | Pure black except AMOLED mode |
| Use muted primary wells for icon circles | Random saturated fills for every icon |

## V2 → V3 color deltas

| Change | V2 | V3 |
|--------|----|----|
| Dark interactive primary | Same `#004A99` | Elevated `#4B9FFF` on dark for controls |
| AMOLED | Absent | Documented palette |
| Splash color | `#390754` drift | Align to brand primary |
| Semantic dark BGs | Often light BGs reused | Dedicated dark semantic backgrounds |
| Hex in features | Common | Forbidden in review checklist |
