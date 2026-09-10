# Phase 5 Workflow Consolidation

Date: 2026-09-09

## Communication: Compose

`/communication/compose?channel=sms|email|whatsapp` is the new shared Compose entry point. It provides channel selection while reusing the existing provider-specific forms for recipient scope, templates, placeholders, scheduling, preview behavior, attachments/media, and send actions.

The reused forms are:

- `communication.partials.sms-form`
- `communication.partials.email-form`
- `communication.partials.whatsapp-form`

The original provider behavior remains unchanged. SMS sender selection, email attachments and queueing, WhatsApp media, rate limiting, progress, retry, delivery reports, and scheduling are still handled by their original controller methods and routes.

### Communication routes retained

- `communication.send.sms`
- `communication.send.sms.submit`
- `communication.send.sms.progress`
- `communication.send.email`
- `communication.send.email.submit`
- `communication.send.email.progress`
- `communication.send.whatsapp`
- `communication.send.whatsapp.submit`
- `communication.send.whatsapp.progress`
- `communication.send.whatsapp.retry`

The old channel pages remain valid compatibility entry points for bookmarks and deep links. No provider integration, queue job, scheduling model, delivery report, or API route was removed.

## Academics: Marks Entry

The repository already had a matrix workflow backed by `ExamMarkEntryService` and `ExamMarkEntryAuditService`. The existing matrix selector is now the clearly named primary **Marks Entry** workflow.

Primary routes:

- `academics.exam-marks.matrix.edit`
- `academics.exam-marks.matrix.view`
- `academics.exam-marks.matrix.store`

Legacy routes intentionally retained for deep links and backward compatibility:

- `academics.exam-marks.bulk.form`
- `academics.exam-marks.bulk.edit`
- `academics.exam-marks.bulk.edit.view`
- `academics.exam-marks.bulk.store`
- `academics.exam-marks.bulk.draft`
- `academics.exam-marks.edit`
- `academics.exam-marks.update`

Calculations, validation, grading rules, permissions, draft autosave, submit-for-review behavior, and publishing routes were not changed.

## Phase 5B candidate classification

### Teaching allocations: retain distinct workflows

Stream-level assignment, subject teacher maps, class-teacher assignment, and staff-centric assignments use different relationships and user jobs. A future shared allocation shell is plausible, but one write form would risk changing assignment semantics and permissions.

### Fee reductions: retain discounts and concessions

Discounts include templates, allocations, approvals, replication, and sibling bulk actions. Fee concessions use a separate resource/controller lifecycle. They need shared navigation/reporting before shared write UI.

### Optional charges: retain optional fees and activity fees

Optional fees include imports and allocations. Activity fees include rosters, attendance, records, and parent requests. Their financial relationship does not make their operational workflows duplicates.

### Expenses: do not flatten statement analysis

Expense statements include parsing, grouping, classification, submission, approval, rejection, reversal, and transaction-level actions. A future tab shell can contain these states, but a single merged form would be unsafe.

### Homework and diary: hub candidate, not yet migrated

Homework, diary entries, submissions, marking, and bulk diary entry share an educational context but expose different teacher and student actions. Role-specific route mapping is required first.

### Attendance insights: read-only tab candidate

Records, at-risk, and consecutive absence views share attendance data, but notification and update actions differ. A tabbed insights shell is plausible while retaining distinct actions.

### Timetable: retain generation and editor routes

Viewing timetables and whole-school generation are different jobs. Feasibility, generation, run editing, locking, substitutions, replication, and publishing must remain distinct actions in any future shell.

### Report-card publishing: scope-picker candidate

Single-card, bulk, class, filtered, and no-notification publishing have different side effects. A shared scope picker can eventually sit above the existing actions; underlying routes should remain explicit until thoroughly tested.

## Validation and concerns

- Blade compilation, PHP syntax checks, editor diagnostics, route registration, and frontend asset compilation passed for the Compose changes.
- Existing channel forms remain the source of truth for provider-specific validation and delivery behavior.
- The broader local test environment has unrelated database/migration failures; those should be stabilized before using full-suite workflow tests as a release gate.