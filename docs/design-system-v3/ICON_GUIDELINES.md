# Icon Guidelines — Design System V3

## Baseline

| Rule | Spec |
|------|------|
| Library | `@expo/vector-icons` **Ionicons** (default) |
| Primary style | **Outline** for navigation and chrome; **Filled** for selected/active |
| Sizes | 16 (inline), **20** (list), **24** (nav / actions), 28–32 (hero wells) |
| Touch | Icon-only controls ≥ 44dp hit area |
| Color | `palette.textMain` / `textSub` / `primary` — never random hex |
| No emoji | Do not use emoji as UI icons |

## Families

| Family | When to use |
|--------|-------------|
| **Outlined** | Default chrome, list trailing actions, form adornments |
| **Filled** | Active bottom-tab, selected chip leading icon, emphasis |
| **Rounded wells** | Circular/squircle background (`primaryMuted` or semantic bg) behind icon — dashboard quick actions, settings rows |
| **Accent / gradient wells** | Module hubs and marketing moments only; keep glyph itself monochrome white or primary |
| **Status** | Pair icon with semantic color (success check, danger alert) — color never sole signal |

## Domain sets (naming guidance)

Prefer consistent Ionicons names across modules:

| Domain | Examples |
|--------|----------|
| Finance | `wallet-outline`, `card-outline`, `cash-outline`, `receipt-outline` |
| Education | `school-outline`, `book-outline`, `ribbon-outline`, `create-outline` |
| People | `people-outline`, `person-outline`, `id-card-outline` |
| Transport | `bus-outline`, `car-outline`, `navigate-outline` |
| Reports | `bar-chart-outline`, `pie-chart-outline`, `analytics-outline` |
| Communication | `megaphone-outline`, `chatbubble-outline`, `mail-outline` |
| System | `settings-outline`, `notifications-outline`, `search-outline`, `shield-checkmark-outline` |

Document any custom SVG assets under `apps/admin/assets/` with 24×24 viewBox and primary-tintable fills.

## Badges on icons

| Badge | Style |
|-------|-------|
| `NEW` | Tiny overline, danger/orange pill, top-end of icon well — sparingly |
| Count | Capsule on bell; max “9+”; semantic danger or brand |
| Status dot | 8dp circle, semantic tone, not overlapping glyph baseline |

## Empty-state & dialog icons

- Large well: 72–80dp circle, `surfaceMuted` or semantic bg
- Glyph: 32–40dp, primary or semantic fg
- Same treatment for success dialogs (branded, not system `Alert` icon only)

## Do / Don't

| Do | Don't |
|----|-------|
| One library (Ionicons) app-wide | Mix Feather + Material + emoji |
| Match outline/fill to state | Random filled icons in inactive nav |
| Tint with tokens | Hardcoded `#4B9FFF` in JSX |
| Align icons to 24 grid | Stretch SVGs unevenly |
| Use chevron-forward for drill-in | Unicode `>` or `←` as navigation |
