# Design Tokens — Design System V3

> Master token catalog and **V2 → V3 migration map**. Atomic details: [COLOR_SYSTEM.md](./COLOR_SYSTEM.md), [TYPOGRAPHY.md](./TYPOGRAPHY.md), [SPACING_SYSTEM.md](./SPACING_SYSTEM.md), [ANIMATION_GUIDE.md](./ANIMATION_GUIDE.md).

## Source of truth

| Layer | Location |
|-------|----------|
| Spec (this doc) | `docs/design-system-v3/` |
| Runtime (today / Stage 1) | `mobile-app/packages/ui/src/theme/tokens.ts` |
| Runtime access | `useTheme()` → `palette`, `typography`, `spacing`, `radius`, `elevation`, `semantic`, `motion` |
| Branding overrides | `BrandingProvider` / `AppThemeProvider` |

**Rule:** Screens and feature components consume tokens via `useTheme()` only. No local color/spacing constants.

## Token groups

```
Color          → palette.*, semantic.*
Typography     → typography.*
Spacing        → spacing.*
Radius         → radius.*
Elevation      → elevation[0..5]
Motion         → motion.duration.*, motion.easing.*
Opacity        → opacity.*
Z-index        → zIndex.*
```

## Color tokens (summary)

See [COLOR_SYSTEM.md](./COLOR_SYSTEM.md) for full light/dark/AMOLED tables.

Required palette keys:

```
primary, primaryDark, primaryLight, primaryMuted, secondary
background, surface, surfaceRaised, surfaceMuted, surfaceOverlay
textMain, textSub, textMuted, textOnPrimary, textLink
border, borderSubtle, disabled, disabledBg
```

Required semantic tones: `brand | success | warning | danger | info` each with `fg`, `bg`, `border`.

## Typography tokens (summary)

See [TYPOGRAPHY.md](./TYPOGRAPHY.md).

Required roles:

```
displayLarge, display, headlineLarge, headline, title, titleSmall,
subtitle, bodyLarge, body, bodyMedium, button, label, caption, overline, tiny
```

Each role: `{ fontSize, lineHeight, fontWeight, letterSpacing }`.

## Spacing / radius / elevation (summary)

See [SPACING_SYSTEM.md](./SPACING_SYSTEM.md).

```
spacing: 4, 8, 12, 16, 20, 24, 32, 40, 48, 56, 64
radius:  control=18, card=24, sheet=28, xl=32, chip=full
elevation: 0–5 (card → fab)
```

## Motion tokens

| Token | Value | Use |
|-------|------:|-----|
| `motion.duration.fast` | 150ms | Ripple, press, chip select |
| `motion.duration.medium` | 250ms | Tab change, fade, sheet snap |
| `motion.duration.slow` | 400ms | Page transition, hero expand |
| `motion.easing.standard` | ease-in-out / cubic(0.4,0,0.2,1) | Default |
| `motion.easing.emphasized` | cubic(0.2,0,0,1) | Sheets, dialogs |
| `motion.easing.decelerate` | cubic(0,0,0.2,1) | Enter |
| `motion.easing.accelerate` | cubic(0.4,0,1,1) | Exit |

Respect `prefers-reduced-motion` / AccessibilityInfo: reduce to opacity cross-fades ≤ 150ms or instant.

## Opacity tokens

| Token | Value | Use |
|-------|------:|-----|
| `opacity.disabled` | 0.4 | Disabled controls |
| `opacity.pressed` | 0.72 | Press feedback when not using ripple |
| `opacity.scrim` | 0.45 | Modal dim (dark-aware) |
| `opacity.hover` | 0.08 | Tablet/web hover wash |

## Z-index

| Token | Value | Use |
|------:|------:|-----|
| `zIndex.base` | 0 | Content |
| `zIndex.sticky` | 10 | Sticky search / compact 360 bar |
| `zIndex.fab` | 20 | FAB |
| `zIndex.nav` | 30 | Bottom nav |
| `zIndex.sheet` | 40 | Bottom sheet |
| `zIndex.dialog` | 50 | Dialogs |
| `zIndex.toast` | 60 | Toasts / snackbars |

## V2 → V3 migration map

### Colors

| V2 | V3 action |
|----|-----------|
| `COLORS.primary` `#004A99` | Keep brand; add dark-elevated interactive primary |
| Surface hierarchy | Keep; add AMOLED variants |
| `SEMANTIC` | Add dark-specific `bg` values |
| Hardcoded feature hex | Delete; map to semantic/palette |

### Typography

| V2 | V3 action |
|----|-----------|
| `TYPOGRAPHY` (display→overline) | Extend with displayLarge, headlineLarge, titleSmall, subtitle, bodyLarge, button, label, tiny |
| `FONT_SIZES` | Soft-deprecate; ban in new code |

### Spacing

| V2 | V3 action |
|----|-----------|
| `SPACING` 4–48 + intermediates | Add `56`, `64`; rename aliases to readable `mdSm` / `mdLg` (keep old keys as aliases) |

### Radius

| V2 | V3 action |
|----|-----------|
| `card: 16`, `control: 12` | **`card: 24`, `control: 18`**; add `sheet: 28` |
| `xl: 20`, `2xl: 24` | Align names; primary card radius is 24 |

### Elevation

| V2 | V3 action |
|----|-----------|
| Levels 0–4 | Add level 5 (FAB); document semantic names; prefer border+surface |

### Motion

| V2 | V3 action |
|----|-----------|
| None | **Add** `motion` token group |

## Stage 1 implementation checklist (code — after doc approval)

1. Update `tokens.ts` with V3 radius, spacing 56/64, motion, extended typography, AMOLED keys.
2. Extend `ThemeContext` to expose `motion`, `radius.sheet`, dark primary elevation.
3. Keep backward-compatible aliases (`3xs` → `mdSm`, `fontSizes` still exported).
4. Add eslint/custom check: no hex literals under `apps/admin/src/features` (allowlist tests/stories).
5. Align splash `backgroundColor` with primary.

## Consumption example

```tsx
const { palette, typography, spacing, radius, elevation, motion } = useTheme();

<View
  style={[
    elevation[2],
    {
      backgroundColor: palette.surfaceRaised,
      borderRadius: radius.card,
      padding: spacing.mdLg,
      borderWidth: 1,
      borderColor: palette.borderSubtle,
    },
  ]}
>
  <Text style={{ ...typography.headline, color: palette.textMain }}>KES 12,400</Text>
  <Text style={{ ...typography.overline, color: palette.textMuted }}>COLLECTED TODAY</Text>
</View>
```
