# Animation Guide — Design System V3

> Motion separates template UIs from premium products. Use **react-native-reanimated** (already in Admin) for UI motion; keep animations purposeful and tokenized.

## Duration tokens

| Name | ms | Use |
|------|---:|-----|
| Fast | **150** | Press, ripple, chip toggle, icon swap |
| Medium | **250** | Tab indicator, fade-in lists, sheet partial |
| Slow | **400** | Screen push/pop, hero expand, dialog enter |

Never invent 180/300/500 without adding a token.

## Easing

| Name | Feel | Use |
|------|------|-----|
| Standard | Balanced | Most property changes |
| Emphasized | Confident settle | Sheets, dialogs |
| Decelerate | Soft enter | Elements appearing |
| Accelerate | Quick exit | Dismissals |

## Reduced motion

When `AccessibilityInfo.isReduceMotionEnabled`:

- Replace travel animations with **opacity** cross-fades ≤ 150ms
- Skip staggered list entrance
- Keep essential feedback (button opacity / haptic if available)

## Recipes

### Page / stack transition

- Default React Navigation slide; duration ~ **slow** (400)
- Shared: fade content 0 → 1 over medium after focus
- Modal stacks: slide up from bottom (sheet-like)

### Card / list entrance

- Opacity 0 → 1 + translateY 8 → 0 over **medium**
- Optional stagger 30–40ms per row (cap 6 rows)
- Skip if reduced motion

### Button press

- Scale 1 → 0.98 or opacity to `opacity.pressed` over **fast**
- Primary buttons may use platform ripple / android ripple with primary color

### Loading shimmer

- Use `SkeletonLoader` / `SkeletonListRows`
- Shimmer sweep ~1200ms loop, subtle; pause when offscreen
- Skeleton geometry must match final layout (KPI grid, list rows)

### FAB

- Enter: scale 0.8 → 1 + fade, **medium**, emphasized easing
- Extended FAB label: width animation **medium**
- Hide on scroll down / show on scroll up (optional, medium)

### Bottom navigation

- Active icon: color interpolate to primary + optional indicator bar width animation **fast**
- Center elevated action (if introduced): soft glow pulse only on first session; then static

### Search

- Focus: elevation 1 → 2, border to primary, **fast**
- Expand cancel button: fade **fast**

### Expand / collapse (filters, accordions)

- Height + opacity **medium**; use Reanimated layout animations where stable
- Chevron rotate 0 → 180 over **fast**

### Snackbars / toasts

- Slide from bottom + fade, **medium** enter / **fast** exit
- Auto-dismiss 3–4s; respect Explore-by-Touch

### Dialogs & bottom sheets

- Scrim fade **medium**
- Sheet: translateY full → 0, **slow**, emphasized
- Dialog: scale 0.96 → 1 + fade, **medium**

### Charts

- Series draw / bar grow once on mount, **slow**
- No continuous ornamental animation
- Provide static final frame immediately if reduced motion

### Success / branded feedback

- Checkmark draw or scale-in **medium**
- Confetti / heavy celebration: **avoid** in Admin; prefer calm branded success dialog

## Haptics (optional, Android)

| Event | Feedback |
|-------|----------|
| Primary success (approve, payment recorded) | Light success |
| Destructive confirm | Warning |
| Selection change | Soft tick (rare) |

Never haptic-spam on scroll.

## Performance

- Target **60 FPS** on mid-tier Android (Galaxy A-class)
- Animate `transform` and `opacity` only when possible
- Avoid animating flexbox layout on large trees; prefer Reanimated shared values
- Cancel animations on unmount

## Do / Don't

| Do | Don't |
|----|-------|
| Tokenized durations | Random `duration: 320` |
| Motion that clarifies state | Parallax noise on every card |
| Honor reduced motion | Infinite decorative loops |
| Match skeleton to layout | Spinner-only on rich dashboards |
