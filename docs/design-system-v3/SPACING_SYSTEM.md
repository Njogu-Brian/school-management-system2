# Spacing System — Design System V3

> Strict spacing, radius, and elevation scales. Ban ad-hoc values (`7`, `13`, `17`).

## Spacing scale

Base unit: **4**. All layout spacing must be one of:

| Token | px | Common use |
|-------|---:|------------|
| `2xs` / `xs` | 4 | Icon–label gaps, tight stacks |
| `sm` | 8 | Chip gaps, dense list padding |
| `md-sm` (`3xs`) | 12 | Card internal gaps, compact rows |
| `md` | 16 | Screen horizontal padding, default card padding |
| `md-lg` (`2md`) | 20 | Comfortable card padding (V3 preference for premium cards) |
| `lg` | 24 | Section gaps, sheet padding |
| `xl` | 32 | Large section breaks, bottom scroll inset |
| `2xl` (`2lg`) | 40 | Hero internal padding |
| `3xl` / `xxl` | 48 | Major breaks, empty-state vertical rhythm |
| `4xl` | 56 | Rare; large empty illustration clearance |
| `5xl` | 64 | Screen-level top/bottom breathing room |

### Named aliases (Stage 1 target in `SPACING`)

```
xs: 4
sm: 8
mdSm: 12    // was 3xs
md: 16
mdLg: 20    // was 2md
lg: 24
xl: 32
2xl: 40     // was 2lg
3xl: 48     // was xxl
4xl: 56     // NEW
5xl: 64     // NEW
```

### Screen padding defaults

| Context | Horizontal | Vertical |
|---------|------------|----------|
| Phone content | `md` (16) | section `lg` (24) |
| Premium card padding | `mdLg` (20) | `mdLg` (20) |
| Bottom sheet | `lg` (24) | top `md`, bottom safe-area + `lg` |
| List row | `md` horizontal | `md-sm`–`md` vertical (min height 56–64) |

### Forbidden

- Magic numbers outside the scale
- `paddingHorizontal: 16` as a raw literal when `spacing.md` exists — always reference tokens
- Compensating cramped layout with uneven “optical” padding that invents new values

## Radius scale

V3 raises radii toward the premium 20–28 card feel.

| Token | px | Alias | Usage |
|-------|---:|-------|-------|
| `xs` | 4 | — | Tiny indicators |
| `sm` | 8 | — | Dense nested elements |
| `md` | 12 | — | Small chips (non-pill) |
| `control` | **18** | Medium | Inputs, search, compact buttons |
| `card` | **24** | Large | Cards, list rows, empty-state containers |
| `sheet` | **28** | — | Bottom sheet top corners |
| `xl` | **32** | Extra Large | Hero panels, dialogs, large empty cards |
| `full` | 9999 | `chip` / `pill` | Filter chips, avatars, FAB circle |

### V2 → V3 radius migration

| Role | V2 | V3 |
|------|---:|---:|
| Control / input | 12 | **18** |
| Card / list item | 16 | **24** |
| Empty icon circle | 24 | 24–32 (icon well) |
| Sheet | undocumented | **28** |
| Dialog | undocumented | **32** |

## Elevation & depth

Prefer **surface step + soft border** over heavy Material shadows.

| Level | Name | Treatment | Usage |
|------:|------|-----------|-------|
| 0 | Flat | No shadow; same as parent | Inline text, dividers |
| 1 | Card | Soft border `borderSubtle` OR elevation 1 | List rows, search bars |
| 2 | Floating card | Border + light shadow (opacity ≤ 0.08) | KPI cards, chart cards |
| 3 | Sheet | Overlay surface + top radius; optional shadow | Bottom sheets |
| 4 | Dialog | Overlay + stronger dim scrim | Modals, permission dialogs |
| 5 | FAB / Nav | Soft colored glow or elevation 3 | FAB, center Explore-style nav (if used) |

### Semantic elevation map

| Component | Level |
|-----------|------:|
| List row / registry card | 1 |
| KPI / Chart / Hero | 2 |
| Bottom sheet | 3 |
| Dialog / branded alert | 4 |
| FAB | 5 |
| Bottom navigation bar | 2–3 (surfaceRaised + soft top border) |

Shadow color: tint with primary (`#004A99`) at low opacity — not pure black — except AMOLED where softer neutral shadows are fine.

## Dividers

- Default: **spacing**, not lines
- When needed: 1px `borderSubtle`, inset from card edges (do not span full bleed under icons)
- Never use high-contrast black rules

## Touch targets

| Element | Min size |
|---------|----------|
| Buttons, list rows, nav items | 48 × 48 |
| Icon-only buttons | 44 × 44 with hitSlop |
| Chips | height ≥ 36; prefer 40 |
| 360 tab pills | height ≥ 44 (V2 debt: ~32) |

## Do / Don't

| Do | Don't |
|----|-------|
| `padding: spacing.mdLg` | `padding: 17` |
| `borderRadius: radius.card` | `borderRadius: 10` |
| Section gaps of `lg` / `xl` | Cramped 4px between major sections |
| Soft card borders | Drop shadow stacks that muddy dark mode |
