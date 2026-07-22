# Icon Guidelines — Design System V3

## Baseline (soft-3D — free-standing)

| Rule | Spec |
|------|------|
| Primary treatment | **Soft-3D illustrations** with depth/highlights — each glyph has its **own** color scheme |
| Background | **None** — no colored circle/square well behind icons (KCB-style free-standing glyphs) |
| Component | `@erp/ui` `Soft3DIcon` (`AccentIcon` is an alias) |
| Sizes | 28 (nav compact), **36–44** (list / drawer), **48–56** (quick actions / KPIs), 64–80 (empty / hero) |
| Touch | Icon-only controls ≥ 44dp hit area |
| No emoji | Do not use emoji as UI icons |

## Soft-3D anatomy

```
┌─────────────────────┐
│  soft contact shadow│
│     ┌─────────┐     │
│     │ colorful│     │  ← NO colored circle/square well
│     │ 3D glyph│     │  ← each glyph owns its own palette
│     └─────────┘     │
└─────────────────────┘
```

| Layer | Purpose |
|-------|---------|
| Contact shadow | Soft ellipse under the glyph only (depth) |
| Glyph | Soft-3D SVG with unique colors (gold coins, green notes, cyan people, orange card, …) |

**Do not** place icons inside a solid/gradient colored square or circle. Icons sit directly on the parent surface (section card / drawer / tab bar).

## Families

| Family | When to use |
|--------|-------------|
| **Soft-3D free-standing** | Dashboard quick actions, KPIs, empty states, bottom tabs, drawer, settings, FAB |
| **Chrome outline** | Dense form adornments, chevrons, tiny meta (16–18dp) |
| **Status** | Soft-3D glyph whose colors convey meaning — color never sole signal |

## Domain glyph keys

| Domain | Glyph keys / Ionicons aliases |
|--------|-------------------------------|
| Home / Dashboard | `home`, `grid` |
| Finance | `wallet`, `cash`, `card`, `receipt` |
| Education | `school`, `book`, `attendance` |
| People / HR | `people`, `person`, `briefcase`, `leave`, `payroll`, `clock` |
| Admissions | `admissions`, `person-add` |
| Approvals | `approvals`, `checkbox`, `checkmark` |
| Transport / Ops | `bus`, `car`, `clipboard`, `visitor` |
| Reports | `chart` |
| Communication | `megaphone`, `chat`, `mail` |
| System | `settings`, `notifications`, `search`, `shield` |

Optional later: WebP assets under `apps/admin/assets/icons/3d/`. Default remains SVG glyphs in `@erp/ui`.

## Navigation icons

| Surface | Spec |
|---------|------|
| Bottom tabs | Free-standing soft-3D glyphs; active tab slightly lifts |
| Drawer | Soft-3D glyphs ~36dp; compact frosted drawer |
| Header actions | Chrome outline or small soft-3D — not oversized |

## Empty-state & dialog icons

- Large soft-3D glyph: 72–80dp — no colored well behind it

## Do / Don't

| Do | Don't |
|----|-------|
| Free-standing multi-color 3D glyphs | Colored circle/square wells behind every shortcut |
| Unique palette per action (cash≠approvals≠students) | One purple fill for all module icons |
| Soft contact shadow under glyph | Flat 2D Ionicons as the hero language |
| Map domain actions to glyph keys | Random unrelated glyphs |
| Use chevron-forward for drill-in | Unicode `>` as navigation |
