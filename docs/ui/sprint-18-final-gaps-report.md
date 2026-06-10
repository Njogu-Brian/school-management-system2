# Sprint 18 — Final Gaps Phase (Library Circulation, GL, Assets, Attachments)

This sprint closes the four remaining gaps identified at the end of Sprint 17:

1. Library circulation (issue / return / renew)
2. General ledger, trial balance and balance sheet reporting
3. Asset creation, editing and staff assignment from mobile
4. Attachment upload for expenses and medical certificates

No new migrations were required — all underlying models and tables
(`Book`, `BookCopy`, `BookBorrowing`, `LibraryCard`, `LibraryFine`,
`LedgerPosting`, `ExpenseAttachment`, `StudentMedicalRecord.certificate_file_path`)
already existed; only API surface and mobile UI were missing.

## Backend (Laravel)

### Library circulation — `ApiLibraryController`
- `GET /api/library/books` — enriched with `category`, `total_copies`, `available_copies`
  and a derived availability status; new `available_only` filter.
- `GET /api/library/borrowings` — paginated, filters: `status` (`borrowed`, `returned`,
  `overdue`, `all`), `student_id`, `search` (student name/admission or book title).
- `POST /api/library/borrowings` — issue flow: accepts `student_id` + `book_id` (+ `days`),
  auto-issues an active library card via `LibraryService::issueCard` when missing, picks the
  first available copy, then delegates to `LibraryService::borrowBook` (limit + availability
  rules enforced by the service, surfaced as 422s).
- `POST /api/library/borrowings/{id}/return` — optional `condition`; runs
  `LibraryService::returnBook` (fine calculation + copy/card/book counters).
- `POST /api/library/borrowings/{id}/renew` — optional `days` (default 14).

### General ledger — new `ApiLedgerController`
- `GET /api/ledger/postings` — paginated `LedgerPosting` feed with `account_code`,
  `dr_cr`, `date_from`, `date_to` filters.
- `GET /api/ledger/trial-balance` — DR/CR totals per account code with grand totals.
- `GET /api/reports/balance-sheet` — derived snapshot:
  cash & bank (collections − expense payments), accounts receivable (outstanding
  invoice balances), fixed assets at cost (active/in-repair), inventory at cost,
  accounts payable (approved unpaid expenses) and net position.

### Assets — `ApiFixedAssetsController`
- `POST /api/assets` — register asset (validation mirrors the web controller, unique tag,
  optional staff assignment, `created_by` stamped).
- `PUT /api/assets/{id}` — full update incl. `assigned_staff_id` (nullable to unassign).
- Detail serializer now exposes `assigned_staff_id` for edit prefill.

### Attachments
- `POST /api/expenses/{id}/attachments` — multipart upload (10 MB; jpg/png/pdf/doc/xls)
  to the public disk under `expense-attachments/`; gated by `ExpensePolicy::view`.
- `DELETE /api/expenses/{id}/attachments/{attachmentId}` — removes file + row.
- Expense detail response now includes an `attachments` array (`file_name`, `url`,
  `uploaded_by`, `uploaded_at`).
- `POST /api/students/{studentId}/medical-records/{id}/certificate` — multipart upload
  (jpg/png/pdf) stored under `medical-certificates/`, sets `certificate_file_path`.
- Medical record serializer now returns `certificate_url`.

All controllers pass `php -l`; all 15 new routes verified via `php artisan route:list`.

## Core package (`@erp/core`)

- `client.ts` — new `postMultipart()` helper (FormData + identity transform).
- `operations.api.ts` — `BorrowingRecord`, `FixedAssetPayload`; methods `listBorrowings`,
  `issueBook`, `returnBook`, `renewBorrowing`, `createAsset`, `updateAsset`,
  `uploadMedicalCertificate`; `LibraryBookRecord` extended with copy counts;
  `MedicalRecordRow.certificate_url`.
- `reports.api.ts` — `ExpenseAttachmentRecord`, `LedgerPostingRecord`, `TrialBalanceData`,
  `BalanceSheetData`, `UploadFileInput`; methods `uploadExpenseAttachment`,
  `deleteExpenseAttachment`, `listLedgerPostings`, `getTrialBalance`, `getBalanceSheet`;
  `ExpenseDetailRecord.attachments`.
- `queryKeys.ts` — `operations.borrowings`, `reports.ledgerPostings`, `reports.trialBalance`,
  `reports.balanceSheet`.
- New hooks: `useInfiniteBorrowings`, `useIssueBook`, `useReturnBook`, `useRenewBorrowing`,
  `useCreateAsset`, `useUpdateAsset`, `useUploadMedicalCertificate`,
  `useInfiniteLedgerPostings`, `useTrialBalance`, `useBalanceSheet`,
  `useUploadExpenseAttachment`, `useDeleteExpenseAttachment` — all with targeted cache
  invalidation (borrowings + books + ops summary; asset detail + registry; expense detail;
  student medical records).

## Admin app

### Library (Operations stack)
- `LibraryBooksScreen` — copy availability (`x/y available`) per book; header actions
  for **Circulation** and **Issue a book**.
- New `LibraryCirculationScreen` — borrowings registry with Out / Overdue / Returned / All
  chips, search, overdue badges, fine display, and inline **Return** / **Renew +14d**
  actions with confirmation.
- New `IssueBookScreen` — student picker (search over full student registry) + book picker
  (availability-aware, unavailable disabled) via bottom sheets, loan-period input,
  auto-card-issue notice.

### Assets (Operations stack)
- New `AssetFormScreen` — create + edit modes sharing one form: tag, name, category,
  location, serial, purchase date/cost, notes, and a staff-assignment bottom sheet
  (searchable, clearable). Edit mode prefills from asset detail.
- `AssetsListScreen` — **Register asset** button; `AssetDetailScreen` — **Edit** button.

### Finance reporting (Reports stack)
- New `BalanceSheetScreen` — KPI cards (assets / liabilities / net position), itemised
  asset & liability sections, embedded trial-balance breakdown from posted ledger entries,
  and a methodology note.
- New `LedgerScreen` — infinite posted-entries feed with account filter chips and
  debit/credit badges.
- Both wired into Reports Hub and Finance Dashboard quick actions.

### Attachments
- `expo-document-picker` added to the admin app (SDK 54 compatible).
- `ExpenseDetailScreen` — new **Attachments** section: open files in browser, delete with
  confirmation, and **Attach receipt / document** (image/PDF picker → multipart upload).
- `HealthTab` (Student 360) — clinic records re-rendered as cards with
  **View certificate** / **Attach (or Replace) certificate** actions per record.

### Navigation & deep linking
- Routes: `LibraryCirculation`, `IssueBook`, `AssetForm`, `BalanceSheet`, `Ledger`.
- Deep links: `library/circulation`, `library/issue`, `assets/new` (ordered before
  `assets/:assetId`), `balance-sheet`, `ledger`.

## Verification
- `php -l` clean on all 5 touched controllers + routes file.
- `php artisan route:list` shows all 15 new endpoints.
- `npm run typecheck` (admin app + workspaces) passes.
- No linter errors in `@erp/core` or the admin app.

## Remaining known gaps
- Chart of accounts is implicit (account codes on postings only) — a managed CoA would
  need new tables.
- Balance sheet is a derived operational snapshot, not a full double-entry statement
  (only expense payments post to the ledger today).
- Library reservations and fine payment collection have models but no mobile UI yet.
