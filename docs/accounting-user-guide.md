# Accounting & Expense Module — User Guide

This guide explains how to use the school finance **accounting system**: chart of accounts, expenses, vouchers, petty cash, fee posting, payroll GL, budgets, and reports.

---

## 1. Getting started

### One-time setup

Run these commands on the server (once per environment):

```bash
php artisan migrate
php artisan db:seed --class=ChartOfAccountsSeeder
```

This creates:

- Default **chart of accounts** (cash, bank, expenses, revenue, payroll payable)
- **Expense categories** in a tree (e.g. Fuel → Diesel, Petrol)
- A **General Petty Cash** fund
- Current **fiscal year** (e.g. FY 2026)

### Where to find things

In the admin menu: **Expenses → Expense Management**

| Sub-menu | Purpose |
|----------|---------|
| All Expenses / New Expense | Vendor bills and internal expenses |
| Payment Vouchers | Pay approved expenses |
| Statement Analyzer | Import M-Pesa PDFs |
| Expense Categories | Spending groups linked to GL |
| **Accounting** | Chart of accounts, journals, petty cash, reports |

---

## 2. Chart of Accounts (COA)

**Path:** Expenses → Accounting → Chart of Accounts

Accounts are organised by type:

| Range | Type | Examples |
|-------|------|----------|
| 1000–1999 | Assets | Cash, Bank, Petty Cash |
| 2000–2999 | Liabilities | Accounts Payable, Salaries Payable |
| 3000–3999 | Equity | Retained Earnings |
| 4000–4999 | Revenue | School Fees Income |
| 5000–5999 | Expenses | Fuel, Payroll, Utilities |

### Adding an account

1. Enter **name** (code can be left blank — it is auto-suggested).
2. Choose **account type** or pick a **parent** (child inherits type).
3. **Postable** = used on transactions. **Header** = grouping only.

System accounts (marked “System”) are used by automated posting and should not be deleted.

---

## 3. Expense categories

**Path:** Expenses → Expense Categories

Categories form a **tree**:

- **Group headers** (e.g. “Fuel & Transport”) — organise only, not selectable on expenses
- **Leaf categories** (e.g. “Diesel”, “Petrol”) — used on expense lines

Each leaf category can link to a **GL account** so payments post to the correct expense account.

**Codes** auto-generate when left blank: `FUEL-PETROL` under parent `FUEL`.

---

## 4. Expense workflow (vendor bills)

```
Draft → Submit → Approve/Reject → Payment Voucher → Pay → Journal posted
```

### Step by step

1. **New Expense** — add lines, pick category, amounts.
2. **Submit** — sends for approval.
3. **Approve** — finance approves (or rejects).
4. **Payment Voucher** — create from approved expense (`Payment Vouchers` or expense detail).
5. **Pay voucher** — choose bank account or cash GL account, enter reference, confirm.

**Document numbers** are automatic: `EXP-00001`, `PV-00001`, `JE-00001`, etc.

### What posts to the ledger

When you pay a voucher:

- **Debit:** each expense line’s category GL account (or Miscellaneous `5999`)
- **Credit:** bank/cash account you selected

View the journal: voucher page → link to journal entry, or **Journal Entries**.

---

## 5. M-Pesa statement analyzer

**Path:** Expenses → Statement Analyzer → Upload M-Pesa Statement

1. Upload PDF (password if required).
2. Wait 1–3 minutes for parsing.
3. Review groups — mark as business expense, personal, or ignore.
4. **Generate Expense Drafts** — creates draft expenses for confirmed items.

Then run the normal expense approval and payment flow.

---

## 6. Petty cash

### Funds

**Path:** Accounting → Petty Cash Funds

An imprest fund is tied to a GL account (e.g. `1101 General Petty Cash`) and optional custodian.

### Vouchers

**Path:** Accounting → Petty Cash Vouchers

| Type | Meaning | GL effect |
|------|---------|-----------|
| **Disbursement** | Cash paid out | Dr expense, Cr petty cash |
| **Replenishment** | Top-up from bank | Dr petty cash, Cr bank |

Workflow: **Create** → **Approve** → **Post to Ledger**.

---

## 7. Fee income (school fee receipts)

When you record a **student payment** (Finance → Payments):

- System **auto-posts** to the general ledger (if chart of accounts is seeded).
- **Debit:** cash/bank (from payment method’s bank account GL, or default bank)
- **Credit:** revenue — split by **votehead** if allocated, else School Fees Income `4000`

**Reversing a payment** creates a reversing journal entry automatically.

### Linking bank accounts to GL

Edit bank accounts under **Finance → Bank Accounts** and set the linked **GL account** (e.g. map “Equity Main” → `1011 Main Operating Bank`). Fee and expense payments then credit/debit the correct account.

### Linking voteheads to revenue accounts

Voteheads can have an `account_id` (revenue GL). Fee allocations then credit the correct income line instead of generic school fees.

---

## 8. Payroll and GL

**Path:** HR → Payroll → Periods

| Step | Action | GL |
|------|--------|-----|
| Process payroll | Generates approved payroll records | **Accrual:** Dr `5200` Payroll, Cr `2100` Salaries Payable |
| Mark Paid & Post GL | After salaries are paid from bank | **Payment:** Dr `2100`, Cr bank |

Use **Mark Paid & Post GL** on the payroll period page when net salaries have left the bank.

---

## 9. Manual journal entries

**Path:** Accounting → Manual Journal

Use for adjustments, opening balances, corrections:

1. Set date and description.
2. Add lines — each line is **debit OR credit** (not both).
3. **Debits must equal credits** before posting.

---

## 10. Fiscal periods & year-end

**Path:** Accounting → Fiscal Periods

- Create periods matching your financial year (e.g. 1 Jan – 31 Dec).
- **Close period** when month/year is finalised (administrative marker; prevents confusion about which year you are working in).

Create the next open period before starting a new year.

---

## 11. Budgets

**Path:** Accounting → Budgets

1. Create a budget for a **fiscal period**.
2. Add **budget lines** — expense account + annual/monthly amount.
3. View **Budget vs Actual** — compares budget to GL actuals for that period.

Positive variance = under budget; negative = over budget.

---

## 12. Financial reports (GL-based)

**Path:** Accounting → GL Reports

| Report | What it shows |
|--------|----------------|
| **Trial Balance** | All accounts — debits, credits, balances for a date range |
| **Profit & Loss** | Revenue vs expenses, net profit |
| **Balance Sheet** | Assets, liabilities, equity as of a date |

All reports read from **posted journal entries**.

---

## 13. Quick reference — document numbers

| Document | Prefix example |
|----------|----------------|
| Expense | EXP-00001 |
| Payment voucher | PV-00001 |
| Petty cash voucher | PCV-00001 |
| Journal entry | JE-00001 |
| Fee receipt | RCPT/2026-0001 |

Configure prefixes under **Finance → Document Settings** (where supported).

---

## 14. Troubleshooting

| Issue | Fix |
|-------|-----|
| “System account not configured” | Run `php artisan db:seed --class=ChartOfAccountsSeeder` |
| Payment voucher fails on pay | Ensure COA exists; check expense categories have GL links |
| Fee payment not in trial balance | Payment may be swimming (`SWIM-` receipts are excluded) or reversed |
| M-Pesa upload slow | Full-year PDF takes 1–3 minutes — wait, don’t refresh |
| Payroll GL missing | Process payroll first (accrual), then Mark Paid |

---

## 15. Recommended monthly routine

1. **Import / record** all expenses and fee collections.
2. **Reconcile** bank statements (Finance → Bank Statements).
3. **Review** trial balance for the month.
4. **Run** profit & loss.
5. **Compare** budgets vs actuals.
6. **Replenish** petty cash if needed.
7. **Process payroll** → Mark paid when bank transfer completes.
8. **Close** fiscal period when accounts are finalised.

---

*Last updated: June 2026 — matches accounting module v1 (COA, journals, expenses, petty cash, fee GL, payroll GL, budgets, reports).*
