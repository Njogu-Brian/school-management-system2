# Sprint 17 — Write Workflows Phase

Goal: close out the remaining backend-blocked **write** workflows so every major admin
journey (communication, procurement, stock, assets, health, expense approvals, financial
reporting) is fully functional end-to-end from the mobile app.

## New backend endpoints

| Method | Route | Purpose |
| --- | --- | --- |
| POST | `/api/communication/templates` | Create message template (sms / whatsapp / email) |
| PUT | `/api/communication/templates/{id}` | Update template |
| DELETE | `/api/communication/templates/{id}` | Delete template |
| POST | `/api/communication/whatsapp` | Broadcast WhatsApp messages (WasenderAPI via `WhatsAppService`) |
| POST | `/api/requisitions` | Create requisition with line items (status `pending`) |
| POST | `/api/inventory/items/{id}/adjust` | Stock in / out / set-quantity via `InventoryTransaction` |
| POST | `/api/assets/{id}/status` | Asset lifecycle status (`active`, `in_repair`, `retired`, `disposed`) with timestamped notes |
| POST | `/api/students/{studentId}/medical-records` | Log clinic visit / medication / vaccination / incident |
| POST | `/api/expenses/{id}/submit` | Submit draft expense (policy: `ExpensePolicy@submit`) |
| POST | `/api/expenses/{id}/approve` | Approve submitted expense (`ExpensePolicy@approve`) |
| POST | `/api/expenses/{id}/reject` | Reject with required remarks |
| POST | `/api/expenses/{id}/pay` | Create voucher + record payment + post ledger entries (`ExpenseWorkflowService`) |
| GET | `/api/reports/income-statement` | Monthly income (fee payments) vs approved/paid expenses, with totals |

Notes:

- Expense workflow re-uses the existing `ExpenseWorkflowService` (approval audit rows,
  payment vouchers, `LedgerPosting` double entries) and `ExpensePolicy` for authorization.
  `GET /expenses/{id}` now returns `can_submit` / `can_approve` / `can_pay` flags.
- Stock adjustments go through `InventoryTransaction` so the item quantity update logic
  (model boot hook) and audit trail stay consistent with the web portal.
- `out` adjustments are validated against available stock server-side.

## Core package (`@erp/core`)

- `communication.api`: `createTemplate`, `updateTemplate`, `deleteTemplate`, `sendWhatsApp`.
- `operations.api`: `createRequisition`, `adjustInventoryStock`, `updateAssetStatus`, `createMedicalRecord`.
- `reports.api`: `submitExpense`, `approveExpense`, `rejectExpense`, `payExpense`, `getIncomeStatement` + `IncomeStatementData` type; `ExpenseDetailRecord` now carries `can_*` flags.
- New hooks: `useCreateTemplate`, `useUpdateTemplate`, `useDeleteTemplate`, `useSendWhatsApp`,
  `useCreateRequisition`, `useAdjustInventoryStock`, `useUpdateAssetStatus`, `useCreateMedicalRecord`,
  `useSubmitExpense`, `useApproveExpense`, `useRejectExpense`, `usePayExpense`, `useIncomeStatement`.
- All mutations invalidate the relevant list/detail/summary query keys.

## Admin app

### Communication
- **TemplateFormScreen** (new): create/edit templates with channel chips (SMS / WhatsApp / email),
  code, subject (email only), content with placeholder hint.
- **TemplatesList**: "New template" button; removed the read-only banner.
- **TemplateDetail**: Edit + Delete (confirm dialog) actions.
- **SmsCompose**: channel toggle (SMS / WhatsApp). WhatsApp hides sender-ID and SMS-segment
  cost meter; templates list follows the selected channel; send button adapts.

### Operations
- **RequisitionFormScreen** (new): purpose, multi-item builder (name/brand/qty/unit),
  "Pick from inventory" bottom sheet with live search that pre-fills and links
  `inventory_item_id`; removable item chips; submits via `POST /requisitions`.
- **RequisitionsList**: "New requisition" button.
- **InventoryItemDetail**: "Adjust stock" section — Receive (+) / Issue (−) / Set quantity
  chips, quantity + notes, with optimistic refresh of item, list and ops summary.
- **AssetDetail**: status badge + "Update status" chips (Active / In repair / Retired /
  Disposed) with optional notes; replaces the "manage on web" placeholder.

### Students / Health
- **MedicalRecordFormScreen** (new, Students stack): record type chips (checkup, medication,
  vaccination, incident, certificate, other) with conditional fields (medication name/dosage,
  vaccine name/next due), date, clinician, facility, notes.
- **HealthTab (Student 360)**: "Log medical record" button navigating to the form.

### Reports / Finance
- **ExpenseDetail**: full workflow section gated by server-side `can_*` flags —
  Submit for approval; Approve / Reject (remarks required for reject); Mark as paid
  (optional payment method + reference) with confirm dialogs throughout.
- **IncomeStatementScreen** (new): range chips (3/6/12 months), KPI cards (income, expenses,
  net surplus/deficit), monthly bar charts for income and expenses, month-by-month cards with
  net position. Entry points from Reports hub and Finance dashboard.

### Navigation
- New routes: `TemplateForm`, `RequisitionForm`, `MedicalRecordForm`, `IncomeStatement`.
- Deep links: `communication/templates/new`, `operations/requisitions/new`,
  `students/:studentId/medical-records/new`, `reports/income-statement`
  (ordered before their `:id` siblings to avoid conflicts).

## Verification

- `php -l` clean on all 7 touched backend files + `routes/api.php`.
- `php artisan route:list` confirms all 13 new endpoints registered.
- `npm run typecheck` (admin app) passes.

## Remaining known gaps (candidates for future phases)

- Full general ledger / chart of accounts / balance sheet (only `LedgerPosting` basic
  postings exist today).
- Library circulation (issue/return) — no backend models for loans.
- Asset creation/assignment from mobile (status changes are now supported).
- Attachment upload for expenses and medical certificates.
