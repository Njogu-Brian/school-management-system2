# Blade Component Library

Phase 2 adds reusable anonymous Blade components under `resources/views/components`. They use the Phase 1 tokens and Bootstrap/AdminLTE markup conventions. Components are currently adopted by the five pilot screens only.

## Core

```blade
<x-button variant="primary" type="submit">Save changes</x-button>
<x-button variant="danger" :loading="$saving">Delete</x-button>
<x-icon-button label="Edit" variant="subtle"><i class="bi bi-pencil" aria-hidden="true"></i></x-icon-button>

<x-card title="Filters" subtitle="Narrow the results.">
    Content goes here.
</x-card>

<x-page-header eyebrow="Students" title="Students" description="Browse student records.">
    <x-slot:actions><x-button>New student</x-button></x-slot:actions>
</x-page-header>

<x-section title="Profile" description="Basic identity information.">...</x-section>
<x-badge variant="success">Active</x-badge>
<x-divider />
```

Supported button variants are `primary`, `secondary`, `subtle`/`ghost`, `danger`, and `link`. Buttons accept `loading` and `disabled`. Icon buttons require a visible or screen-reader `label`.

## Forms

Form components accept a `name`, optional `label`, `help`, `required`, and normal HTML attributes. They read Laravel's error bag and add `is-invalid`, `aria-invalid`, and an associated error message automatically.

```blade
<x-form.input name="admission_number" label="Admission number" />
<x-form.select name="classroom_id" label="Class" :options="$classrooms" placeholder="All classes" />
<x-form.textarea name="notes" label="Notes" help="Keep this concise." />
<x-form.checkbox name="confirm_duplicate" label="This is a different child" />
<x-form.radio name="status" value="active" label="Active" />
```

For a custom control, use the wrapper and error component:

```blade
<x-form.field name="custom" label="Custom field">
    <input id="custom" name="custom" class="form-control" />
</x-form.field>
```

## Data and Feedback

```blade
<x-data.table caption="Students">...</x-data.table>
<x-data.actions><x-icon-button label="Edit">...</x-icon-button></x-data.actions>
<x-data.pagination :items="$students" />
<x-data.filter-bar :action="route('students.index')">...</x-data.filter-bar>
<x-data.stat-card label="Total students" :value="$count" icon="bi bi-people" />

<x-feedback.alert type="warning" dismissible>Review the highlighted fields.</x-feedback.alert>
<x-feedback.flash />
<x-feedback.empty-state title="No students found" message="Create a student to get started.">
    <x-slot:action><x-button>New student</x-button></x-slot:action>
</x-feedback.empty-state>
<x-feedback.loading label="Loading students" />
<x-feedback.confirmation-modal
    id="confirm-delete"
    title="Delete record?"
    message="This action cannot be undone."
    form="delete-form"
    confirm-label="Delete" />
```

The confirmation modal is intended for migrated forms that previously used native `confirm()`. It submits the referenced form and keeps the existing route, method, and authorization behavior.

`x-page-header` accepts an optional `icon` prop. Module header partials can preserve domain-specific contracts while delegating rendering to the shared component:

```blade
<x-page-header eyebrow="Finance" title="Invoices" icon="bi bi-file-text" :description="$subtitle ?? null">
    <x-slot:actions>{!! $actions !!}</x-slot:actions>
</x-page-header>
```

## Navigation

```blade
<x-nav.breadcrumb :items="['Students' => route('students.index'), 'Create' => null]" />
<x-nav.tabs id="settings-tabs" :items="['general' => 'General', 'branding' => 'Branding']" active="general" />
<x-nav.page-actions><x-button>Save</x-button></x-nav.page-actions>
```

## Migration Rules

- Search for existing partials and local styles before introducing a component.
- Keep component APIs semantic and variant-based; do not create module-colored components.
- Migrate complete representative screens gradually. Do not mass-rewrite the portal.
- Preserve route names, form names, permissions, validation, actions, and business logic.
- Keep native confirmations in untouched modules documented as migration candidates; replace them only when the workflow is migrated to the shared modal.
- Use accessible names, labels, `role`/`aria-*` attributes, and text alternatives for status.

## Existing Duplicate Candidates

These patterns are intentionally not mass-replaced in Phase 2:

- `resources/views/settings/partials/styles.blade.php` and `dashboard/partials/styles.blade.php`: local card, header, tab, and button rules.
- `resources/views/finance/partials/header.blade.php`: finance-specific page header wrapper.
- `resources/views/students/partials/alerts.blade.php`, `partials/alerts.blade.php`, and `finance/invoices/partials/alerts.blade.php`: repeated flash and validation alerts.
- `resources/views/dashboard/partials/flash.blade.php` and `communication/partials/flash.blade.php`: repeated success/error flash rendering.
- `resources/views/students/partials/empty-state.blade.php`: reusable empty state candidate.
- `resources/views/students/partials/action-dropdown.blade.php`: domain-specific table action candidate.
- Native `confirm()` calls across finance, students, settings, and other modules: migrate workflow by workflow because each needs its own form/action context.
- Direct `{{ $paginator->links() }}` calls: candidates for the pagination wrapper.
- Repeated plain `<table>` and filter forms across finance, students, HR, and academics: candidates for data/table and filter-bar adoption.
