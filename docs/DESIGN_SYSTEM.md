# Web Design System Foundation

This is the shared visual foundation for the Laravel web portal. It is designed to work with the existing Bootstrap 5 and AdminLTE architecture. It does not replace either framework and does not begin a Tailwind migration.

## Entry Point

The production layout loads `public/css/app-custom.css` for existing runtime-safe utilities and `public/css/design-system.css` for the centralized design system. The design-system stylesheet is loaded after stacked page styles so pilot components can resolve conflicts predictably.

The design system is currently scoped to `.ds-pilot` component rules. This is intentional: new foundations can be validated on representative screens before modules are migrated.

## Tokens

Tokens use the `--ds-` prefix and semantic names rather than module names:

- Colors: `--ds-color-brand`, `--ds-color-accent`, `--ds-color-page`, `--ds-color-surface`, `--ds-color-text`, `--ds-color-text-muted`, and `--ds-color-border`
- Status: `--ds-color-success`, `--ds-color-warning`, `--ds-color-danger`, and `--ds-color-info`, each with a background token
- Typography: `--ds-font-family`, `--ds-font-size-xs` through `--ds-font-size-2xl`, and normal/medium/semibold/bold weights
- Spacing: `--ds-space-1` through `--ds-space-12`
- Shape and depth: `--ds-radius-sm`, `--ds-radius-md`, `--ds-radius-lg`, `--ds-radius-pill`, and shadow tokens
- Controls: `--ds-control-height-sm`, `--ds-control-height-md`, and `--ds-control-height-lg`
- Layers: dropdown, sticky, and modal z-index tokens
- Breakpoints: `--ds-breakpoint-sm`, `--ds-breakpoint-md`, `--ds-breakpoint-lg`, and `--ds-breakpoint-xl`

Brand values inherit the existing runtime `--brand-*` values set by the Blade layout. Dark mode overrides semantic surface, text, border, and status tokens under `body.theme-dark`.

## Component Conventions

### Buttons

Use Bootstrap button markup and semantic intent classes:

```html
<button class="btn btn-primary">Save changes</button>
<button class="btn btn-secondary">Cancel</button>
<button class="btn btn-ghost-strong">View details</button>
<button class="btn btn-danger">Archive</button>
<button class="btn btn-icon" aria-label="Edit"><i class="bi bi-pencil"></i></button>
<button class="btn btn-primary" data-loading="true" disabled>Saving...</button>
```

The pilot styling maps existing `btn-settings-primary`, `btn-ghost`, and `btn-ghost-strong` classes so current markup remains functional during migration.

### Forms

Use a visible `label`, a real associated control, inline validation text, and helper text only where it improves completion. Use `is-invalid` with `invalid-feedback` for server or client validation. Do not use color as the only error signal.

### Cards and page headers

Use cards for genuinely framed content such as a form section, summary, or table. Keep page headers outside cards when possible. Use `settings-card`, `dash-card`, or `finance-card` only while migrating existing module markup; new shared patterns should use semantic card styling under `.ds-pilot`.

### Tables, badges, alerts, and tabs

- Tables must retain responsive wrappers and have meaningful table headers.
- Badges describe status; they do not replace accessible text.
- Alerts use `alert-success`, `alert-warning`, `alert-danger`, or `alert-info` and should include `role="alert"` for live messages.
- Tabs use Bootstrap's tab behavior, correct `role`, `aria-controls`, and `aria-selected` attributes.

## Naming Rules

- Prefix design tokens with `--ds-`.
- Name tokens by purpose, not module: use `--ds-color-success`, not `--finance-green`.
- Use Bootstrap utility and component classes for layout where they are sufficient.
- Add a shared class only when the pattern occurs across modules or carries accessibility/state behavior.
- Keep module-specific styling in the module until that module is explicitly migrated.

## Migration Rules

1. Inspect the module's route, view, controller contract, permissions, JavaScript, and tests before editing.
2. Add `.ds-pilot` only to a complete representative screen or a deliberately bounded component.
3. Replace local colors, spacing, and shadows with semantic tokens incrementally.
4. Preserve existing route names, form names, actions, validation, permissions, and business logic.
5. Validate desktop, tablet, and mobile widths before expanding the migration.
6. Replace native confirmation dialogs only when the shared confirmation system is available for that workflow.

## Anti-patterns

- Do not add `finance-card-blue`, `settings-card-green`, or other module-colored tokens.
- Do not copy a `<style>` block into another Blade view.
- Do not use inline styles for reusable visual rules.
- Do not use color alone to communicate status or validation.
- Do not make every page a card or add decorative UI without a workflow purpose.
- Do not migrate the portal to Tailwind as part of this foundation.
- Do not change controllers, models, APIs, authorization, financial calculations, or database structures for visual work.

## Pilot Screens

The foundation is currently exercised on:

- Admin dashboard: `resources/views/dashboard/admin.blade.php`
- Student admission form: `resources/views/students/create.blade.php`
- Student list/table: `resources/views/students/index.blade.php`
- System settings: `resources/views/settings/index.blade.php`
- Finance voteheads list/table: `resources/views/finance/voteheads/index.blade.php`