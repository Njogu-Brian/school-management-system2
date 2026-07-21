# Design Review Checklist — Design System V3

> **Gate:** Before merging ANY Admin App UI change, the author and reviewer complete this checklist.  
> Specs: [ANDROID_DESIGN_SYSTEM.md](./ANDROID_DESIGN_SYSTEM.md) · [DESIGN_TOKENS.md](./DESIGN_TOKENS.md) · [COMPONENT_LIBRARY.md](./COMPONENT_LIBRARY.md) · [SCREEN_PATTERNS.md](./SCREEN_PATTERNS.md)

Copy into the PR description:

```
## Design System V3 checklist
- [ ] Tokens / no hardcoding
- [ ] Typography
- [ ] Spacing & radius
- [ ] Components & patterns
- [ ] States (loading/empty/error/offline/success)
- [ ] Navigation & chrome
- [ ] Motion
- [ ] Accessibility
- [ ] Dark mode
- [ ] Responsive / tablet-safe
- [ ] Performance
- [ ] Icons
```

---

## 1. Tokens & hardcoding

- [ ] No hardcoded hex/rgb colors in feature code (use `palette` / `semantic`)
- [ ] No ad-hoc spacing (`7`, `13`, `17`, …) — only spacing scale
- [ ] No ad-hoc `borderRadius` — only `radius.*` (`control` 18, `card` 24, `sheet` 28, …)
- [ ] No new shadow objects — use `elevation` levels
- [ ] No `fontSizes.*` in new/changed code — use `typography.*`
- [ ] Branding overrides only via theme / BrandingProvider

## 2. Typography

- [ ] Clear hierarchy (one focal headline region)
- [ ] Correct roles: hero → `headline`/`headlineLarge`; sections → `title`; body → `body`; meta → `caption`/`overline`
- [ ] Button labels use `typography.button`
- [ ] Supports font scaling without clipped controls

## 3. Spacing & radius

- [ ] Screen horizontal padding = `spacing.md` (16) unless pattern says otherwise
- [ ] Premium cards use ~`spacing.mdLg` (20) padding
- [ ] Section gaps `lg`/`xl` — no cramped major blocks
- [ ] Cards use `radius.card` (24); inputs/buttons `radius.control` (18)
- [ ] Soft `borderSubtle` preferred over harsh full-bleed dividers
- [ ] Touch targets ≥ 44–48 dp

## 4. Components & patterns

- [ ] Reuses `@erp/ui` components — no duplicate SearchBar/FilterChip/ListRow
- [ ] Matches a [SCREEN_PATTERNS.md](./SCREEN_PATTERNS.md) template
- [ ] Status via `StatusBadge` tones — no one-off hex maps
- [ ] Filters: ≤1 visible summary row; extra filters in bottom sheet
- [ ] Module hubs include `DashboardHero` where pattern requires
- [ ] 360 tabs use shared `ScrollableTabBar` / Profile360 shell
- [ ] Headers use shared ScreenHeader / Ionicons back — no Unicode `←`

## 5. States (mandatory)

- [ ] **Loading:** skeleton matching layout (lists → `SkeletonListRows`)
- [ ] **Empty:** `EmptyState` / `ListEmptyState` + contextual CTA
- [ ] **Error:** branded empty/error + Retry
- [ ] **Offline:** `OfflineBanner` and sensible disabled writes
- [ ] **Success:** Toast or SuccessDialog for important mutations
- [ ] **Permission denied:** AccessDenied / EmptyState pattern

## 6. Navigation & chrome

- [ ] Correct stack vs sheet vs dialog choice ([NAVIGATION_GUIDE.md](./NAVIGATION_GUIDE.md))
- [ ] No double headers (stack + custom back + title bar)
- [ ] Safe areas respected (notch, gesture bar, tab bar clearance)
- [ ] FAB / sticky CTA not obscured by bottom nav

## 7. Motion

- [ ] Durations use motion tokens (150 / 250 / 400)
- [ ] Enter/exit for sheets/dialogs follow [ANIMATION_GUIDE.md](./ANIMATION_GUIDE.md)
- [ ] Respects reduced motion
- [ ] No decorative infinite animations
- [ ] Optional haptics only on meaningful success/destructive confirms

## 8. Accessibility

- [ ] Contrast AA for text on surfaces
- [ ] Interactive roles (`button`, `tab`, `search`, `link`) set
- [ ] Icon-only controls have `accessibilityLabel`
- [ ] Charts have text summary / `accessibilityLabel`
- [ ] Color is not the only status signal
- [ ] Focus order sensible on forms

## 9. Dark mode

- [ ] Verified on dark theme (charcoal, not broken contrast)
- [ ] Semantic badges readable on dark surfaces
- [ ] Borders visible (`borderSubtle` / `border`)
- [ ] Heroes/charts not washed out or invisible

## 10. Responsive

- [ ] Phone 360–430 wide: no horizontal overflow
- [ ] Tablet: layout doesn’t break; master-detail where pattern exists
- [ ] Landscape usable for forms/lists (no trapped CTAs)

## 11. Performance

- [ ] No obvious jank on list scroll (target 60 FPS)
- [ ] Heavy work not on JS thread during transitions
- [ ] Images/icons appropriately sized
- [ ] FlatList virtualization for long lists

## 12. Icons

- [ ] Ionicons (or approved asset) — no emoji icons
- [ ] Outline vs filled matches state
- [ ] Sizes 20/24 standard; wells token-tinted

## 13. Content & brand

- [ ] Copy is clear, sentence case, no placeholder lorem in production paths
- [ ] Feels ScholarCore (primary blue, premium calm) — not a third-party clone
- [ ] Success/error dialogs branded when user-facing and important

---

## Reviewer notes

| Severity | Meaning |
|----------|---------|
| Blocker | Hardcoded tokens, missing empty/error, broken a11y targets, double headers |
| Major | Wrong pattern, filter walls, fontSizes, radius drift |
| Minor | Motion polish, sparkline, tablet niceties |

**Fail any Blocker → do not merge.**

---

## Phase 1 reminder

This checklist applies once Stage 1+ implementation begins. **Phase 1 (this documentation kit) must be approved before UI code changes.** After approval, every PR that touches UI is expected to use this gate.
