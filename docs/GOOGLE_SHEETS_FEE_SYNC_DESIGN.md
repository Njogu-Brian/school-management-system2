# Google Sheets ↔ School System Fee Sync – Design

## Is it possible?

**Yes.** You can build an agent (sync service + optional UI) that:

1. **Reads** your Google Sheet (fee voteheads, amounts paid, invoices, fee balances).
2. **Compares** those values with the school system (invoices, invoice_items, payments, balances).
3. **Shows discrepancies** and lets you **approve** which changes to apply.
4. **Applies** approved changes to either the **system** or the **sheet** (or both).
5. **Keeps the income sheet in sync** when changes happen in the system (new/edited invoices, payments collected) by pushing updates to the sheet.

---

## High-level architecture

```
┌─────────────────────┐         ┌──────────────────────────────────────────┐
│   Google Sheet      │         │   School Management System (Laravel)     │
│   (Income / Fees)   │         │   invoices, invoice_items, payments,     │
│   - Voteheads       │  sync   │   voteheads, students                    │
│   - Amounts paid    │ ◄─────► │   Fee comparison already exists (Excel)  │
│   - Fee balances    │         │   + new: apply changes, push to Sheet    │
└─────────────────────┘         └──────────────────────────────────────────┘
         │                                        │
         │  Google Sheets API                     │  DB + Events (invoice/
         │  (read + write)                        │  payment created/updated)
         ▼                                        ▼
┌─────────────────────┐         ┌──────────────────────────────────────────┐
│  Sync Agent / Job   │         │  - Comparison logic (extend existing)     │
│  - Read sheet       │         │  - Approval UI (new)                      │
│  - Compare vs DB   │         │  - Apply service (system or sheet)         │
│  - Push updates    │         │  - Listeners → update sheet on change      │
└─────────────────────┘         └──────────────────────────────────────────┘
```

---

## What you need

### 1. Google Sheets API access

- A **Google Cloud project** with **Google Sheets API** (and optionally **Google Drive API**) enabled.
- **Service account** (recommended for server) or **OAuth 2.0** (if users must use “their” sheet).
- The sheet shared with the service account email (e.g. `xxx@xxx.iam.gserviceaccount.com`) with **Editor** access so the app can read and write.

### 2. Sheet structure (your “income sheet”)

You need a consistent layout so the agent can map columns to the system, for example:

| Student / Admission | Votehead / Fee type | Invoiced | Paid | Balance | Term | Year | Notes |
|---------------------|---------------------|----------|------|---------|------|------|-------|

- **Student** can be admission number or name (admission number is more reliable for matching).
- **Votehead** should match your `voteheads.name` (e.g. “TUITION FEES”) or a configurable mapping.
- **Invoiced / Paid / Balance** are the amounts to compare and optionally update.

You can also use one row per student with multiple columns per votehead (e.g. “Tuition – Invoiced”, “Tuition – Paid”, “Transport – Invoiced”, …). The agent logic will depend on the exact layout you choose.

### 3. In the school system (already there)

- **invoices** – per student, term, year, total, status, paid_amount, balance.
- **invoice_items** – invoice_id, votehead_id, amount (per votehead).
- **payments** – student_id, invoice_id, amount, payment_date.
- **voteheads** – name, id.
- **Fees comparison import** – Excel compare (preview only); we extend this to “Google Sheet + apply after approval”.

---

## Components to build

### A. Google Sheet connector (read + write)

- **Read**: fetch a range (e.g. “Income!A2:H500”) and parse into rows (student identifier, votehead, invoiced, paid, balance, term, year).
- **Write**: update specific cells or rows when:
  - Pushing system data to the sheet after comparison, or
  - After applying approved changes, or
  - When invoices/payments change (see E below).

Use **Google Sheets API v4** (e.g. `spreadsheets.values.get` / `spreadsheets.values.update`). In PHP/Laravel you can use a package such as **google/apiclient** or a thin wrapper around the REST API.

### B. Column mapping

- Config (e.g. in `.env` or a settings table):
  - Sheet ID (or URL).
  - Sheet name and range.
  - Column indices or names: admission number, student name, votehead, invoiced, paid, balance, term, year.
- Map sheet “votehead” text to `voteheads.id` (or name) in your DB.

### C. Comparison (extend existing logic)

- Reuse the same comparison ideas as in **FeesComparisonImportController** (year/term, per-student and optionally per-family totals).
- **Source A**: data from Google Sheet (from A).
- **Source B**: system totals from `invoices`, `invoice_items`, `payments`, and any balance brought forward logic you use.
- Output: list of **discrepancies** (e.g. “Sheet says paid 50,000; system says 45,000” or “Sheet has student X with balance 10,000; system has 15,000”) with a clear “direction” (sheet → system or system → sheet).

### D. Approval and apply

- **UI**: a screen that lists discrepancies and lets the user select which to apply (e.g. checkboxes) and choose direction:
  - **Apply to system**: update system invoices/payments/allocations to match the sheet (with care: avoid overwriting without audit trail).
  - **Apply to sheet**: update the sheet to match the system.
- **Apply to system**: use your existing **InvoiceService**, **PaymentService**, **PaymentAllocationService** (and any posting logic) to add adjustments, extra payments, or invoice amendments, rather than raw SQL.
- **Apply to sheet**: use the connector (A) to write back the correct values to the mapped cells.
- All applied actions should be **logged** (audit_logs / activity_logs) and optionally create **journal entries** if you need accounting trail.

### E. Keep income sheet in sync when system changes

- **Events**: when an **invoice** or **invoice_item** is created/updated, or a **payment** is collected (and allocated), fire Laravel events (e.g. `InvoiceUpdated`, `PaymentRecorded`).
- **Listener / Job**: a listener or queued job that:
  - Recomputes the relevant totals (and votehead breakdown) for the affected student(s) and term/year.
  - Uses the connector (A) to **update the income sheet** (the row(s) for that student/votehead) so the sheet reflects the system.
- You can batch updates (e.g. once per hour) or update in real time; real time keeps the sheet “at par” with the system as you requested.

---

## Security and safety

- **Credentials**: store Google service account JSON in a secure location (e.g. `storage/app/google-credentials.json`) and never commit it. Reference path in `.env` (e.g. `GOOGLE_APPLICATION_CREDENTIALS` or `GOOGLE_SHEETS_CREDENTIALS_PATH`).
- **Scopes**: request only the Sheets (and if needed Drive) scopes required for read/write.
- **Apply to system**: require appropriate permission (e.g. finance manager) and an explicit “Approve” action; consider two-step confirmation for large batches.
- **Audit**: log every “apply” (who, when, which discrepancies, before/after values).

---

## Implementation order

1. **Google Sheets connector** – read a range; then write back to a test range. Configure sheet ID and range in `.env`.
2. **Mapping** – map sheet columns to “student”, “votehead”, “invoiced”, “paid”, “balance”, “term”, “year”.
3. **Comparison** – feed sheet data into a comparison service (extend existing fees comparison logic) and return discrepancies.
4. **UI** – “Sync from Google Sheet” → run comparison → show discrepancies → “Apply selected to system” / “Apply selected to sheet”.
5. **Apply logic** – implement “apply to sheet” first (safer); then “apply to system” with small, reversible steps (e.g. payment adjustments, invoice notes).
6. **Events + push to sheet** – on invoice/payment create/update, enqueue a job to update the income sheet for affected students so fees management and income sheet stay at par.

---

## Summary

| Question | Answer |
|----------|--------|
| Can an agent access my Google Sheet (fee voteheads, amounts paid, invoices, balances)? | Yes, via Google Sheets API (read/write). |
| Can it compare sheet vs system and show discrepancies? | Yes; reuse and extend your existing fees comparison logic. |
| Can it apply changes once approved? | Yes; “apply to sheet” and “apply to system” with approval UI and audit. |
| Can it keep the income sheet updated when system changes? | Yes; events on invoice/payment + job that pushes updated totals to the sheet. |

If you want, the next step is to add a minimal **Google Sheet connector service** and **env/config** in this repo, then wire one “Compare with Google Sheet” route that reuses your existing comparison logic.
