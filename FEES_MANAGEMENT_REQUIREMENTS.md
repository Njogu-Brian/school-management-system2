# Fees Management Module - Requirements Analysis

**Date:** December 16, 2025  
**Status:** Requirements Review & Gap Analysis

---

## Executive Summary

This document outlines the comprehensive fees management requirements and compares them against the current implementation to identify gaps and enhancement opportunities.

---

## Requirements Breakdown

### 1. Voteheads & Charge Types ✅ **IMPLEMENTED**

**Requirements:**
- Create voteheads with different charge types
- Charge types: once per term, yearly, once per family, once only
- Mandatory voteheads (e.g., tuition fees)
- Optional fees (e.g., swimming) linked to extra-curricular activities

**Current Status:**
- ✅ Voteheads with charge types implemented
- ✅ Mandatory/optional flags exist
- ✅ Charge types: `per_student`, `once`, `once_annually`, `per_family`
- ⚠️ **Gap:** Optional fees linked to activities from academic module/timetable needs verification

---

### 2. Fee Structures ✅ **IMPLEMENTED**

**Requirements:**
- Create fee structures from existing voteheads
- Assign to specific classes (streams treated as separate classes)
- Replicate across different classes
- Fee structures are per term (3 terms per academic year)
- Settings for terms and academic years

**Current Status:**
- ✅ Fee structures per class/term implemented
- ✅ Replication feature exists
- ✅ Terms and academic years in settings

---

### 3. Post Pending Fees ✅ **MOSTLY IMPLEMENTED**

**Requirements:**
- Admin selects term, academic year, specific or all classes
- System checks fee structure and posts voteheads/amounts
- Respects charge types (e.g., once per term)
- For already charged terms: only post differences
  - If first time: Grade 2 tuition was 20,000, now 22,000 → add 2,000
  - If reduced: 20,000 to 19,000 → subtract 1,000
  - If no changes: echo and say so
- Always inform what changes have been made
- If no pending fee was posted initially, charge the current fee
- For optional fees: only charge students taking those specific fees

**Current Status:**
- ✅ Post pending fees with diff calculation implemented
- ✅ Logging of changes exists
- ✅ Optional fees only charged to selected students
- ✅ "No changes" message when applicable
- ⚠️ **Enhancement:** Could improve change summary messaging

---

### 4. Optional Fee Allocation ✅ **IMPLEMENTED**

**Requirements:**
- View where admin selects year, term
- Pick class and stream (or class without stream)
- Select specific optional votehead
- Tick students taking that votehead
- Puts them in pending state until post pending fees is actioned

**Current Status:**
- ✅ Optional fee allocation view exists
- ✅ Pending state implemented (`status = 'billed'` creates pending invoice items)
- ✅ Class/stream selection available

---

### 5. Post Pending Fees Logging & Reversal ✅ **IMPLEMENTED**

**Requirements:**
- Log all changes made
- Provide ability to view those changes
- Ability to reverse: whole operation, per class, or per student

**Current Status:**
- ✅ `FeePostingRun` and `PostingDiff` models track all changes
- ✅ View changes functionality exists
- ✅ Reversal implemented (whole operation, per class, per student)

---

### 6. Invoicing ✅ **MOSTLY IMPLEMENTED**

**Requirements:**
- Once student is invoiced, logged in their statement with invoice number
- All voteheads in a specific term within an academic year = one invoice
- Voteheads should have unique dates based on the specific day post pending fees was actioned
- All should belong to one invoice so long as they fall within a specific term in a year
- All should share an identical invoice number
- No two students should have similar invoice numbers
- Have a place to set invoice number and receipt number start digits and prefixes/suffixes

**Current Status:**
- ✅ Invoices created per student/term/year
- ✅ Invoice numbers unique per student
- ✅ Document number service for invoice/receipt configuration exists
- ⚠️ **Gap:** Invoice items should have unique dates based on posting date (currently may not be set)
- ⚠️ **Gap:** Need to verify invoice number uniqueness across all students

---

### 7. Invoice Reversal ✅ **IMPLEMENTED**

**Requirements:**
- An invoice can be reversed as a whole for a specific student
- All information should be logged in the student's statement

**Current Status:**
- ✅ Invoice reversal exists
- ✅ Statement logging implemented

---

### 8. Student Fee View ✅ **IMPLEMENTED**

**Requirements:**
- Admin can view specific student's fees
- Search for a student, select specific term and year
- View and edit invoices
- View payments made in the same view

**Current Status:**
- ✅ Student fee view exists
- ✅ Invoice viewing and editing available
- ✅ Payments visible in same view

---

### 9. Credit/Debit Notes ✅ **IMPLEMENTED**

**Requirements:**
- Section for editing invoices, credit and debit notes
- Select specific student, then select credit or debit note
- Figure and votehead, then post
- Credit adds specific amount, debit deducts
- Always log all credit/debit notes in student's statement for records purposes
- Set credit/debit note numbers in same section we set invoice and receipt numbers

**Current Status:**
- ✅ Credit/debit notes implemented
- ✅ Statement logging exists
- ✅ Document number service supports credit/debit note numbers
- ⚠️ **Enhancement:** Could add dedicated settings page for all document number configuration

---

### 10. Editing Invoice Items ⚠️ **PARTIALLY IMPLEMENTED**

**Requirements:**
- On every line of invoice, make it possible to edit the amount by simply clicking
- Creating a new figure, saving changes
- If it has reduced, create a credit note
- If increased, create a debit note
- Also make it possible to delete credit/debit notes (that way if done, the changes are reversed)

**Current Status:**
- ✅ Credit/debit notes can be created manually
- ✅ Credit/debit notes can be reversed/deleted
- ❌ **Missing:** Inline editing of invoice items (click to edit)
- ❌ **Missing:** Auto-creation of credit/debit notes when invoice item amount changes

---

### 11. Discounts ✅ **IMPLEMENTED**

**Requirements:**
- Create various types of discounts
- Set them to use percentage or amount or both
- Attach to specific voteheads or entire child's invoice
- Types include: sibling discount, referral discount, early repayment discount, transport fee discount, etc.
- Discounts are also set as once, yearly or termly or manual (to be activated by admin)

**Current Status:**
- ✅ Discount templates with types (percentage/amount)
- ✅ Votehead-specific or invoice-level discounts
- ✅ Frequencies: once, yearly, termly, manual
- ✅ Various discount types supported

---

### 12. Discount Setup & Issuing ✅ **IMPLEMENTED**

**Requirements:**
- Views to setup and issue discounts
- In issuing, ensure dynamics like selecting term, academic year, classes or specific students are implemented

**Current Status:**
- ✅ Discount template creation
- ✅ Discount allocation with term/year/class/student selection
- ✅ Bulk sibling discount allocation

---

### 13. Discount Logging & Replication ⚠️ **PARTIALLY IMPLEMENTED**

**Requirements:**
- Discounts once assigned are logged in student's fee statement under the term they have been setup to
- Create ability to replicate specific discounts across terms & classes

**Current Status:**
- ✅ Discounts logged in statements
- ❌ **Missing:** Discount replication feature (replicate across terms & classes)

---

### 14. Payments - Bank Accounts & Methods ✅ **IMPLEMENTED**

**Requirements:**
- Setup bank accounts
- Setup payment methods and link them to specific bank accounts

**Current Status:**
- ✅ Bank accounts management exists
- ✅ Payment methods exist
- ⚠️ **Gap:** Payment methods linked to bank accounts needs verification

---

### 15. Payment Entry ⚠️ **PARTIALLY IMPLEMENTED**

**Requirements:**
- Place to receive payments: type in name of child and enter amount paid
- If the child has siblings, they should appear on the side but greyed out
- Have a button called "share payment" that will allow you to share that payment among all siblings totaling to the initially typed amount
- Have a payment date that can be set by the admin or accountant (different from receipt date which is set automatically)
- Have a place to type in narration where the user will input the transaction code (very important as no two payments should share a transaction code)
- Also have a place to select payment method
- Up there have a place indicating current student's balance and if siblings, every student's balance should be there
- Allow for collection of amount more than the balance but warn that the user is about to overpay, then carry that amount forward

**Current Status:**
- ✅ Payment entry exists
- ✅ Payment method selection exists
- ✅ Transaction code/narration field exists
- ❌ **Missing:** Sibling display (greyed out) in payment form
- ❌ **Missing:** "Share payment" button for siblings
- ❌ **Missing:** Payment date separate from receipt date
- ❌ **Missing:** Transaction code uniqueness validation
- ❌ **Missing:** Sibling balance display
- ❌ **Missing:** Overpayment warning and carry forward

---

### 16. Receipt Generation ✅ **MOSTLY IMPLEMENTED**

**Requirements:**
- Once payment is made, generate a receipt with a unique receipt number
- Details of child: name, admission, class, current term and year
- Payment date, receipt date
- Description of payment and balance or balance carried forward
- Log payment on their receipt number
- Receipt should appear on a new window as a document
- Have a place in settings to update various document headers and footers
- The new window should allow user to view the receipt, download as PDF or print it

**Current Status:**
- ✅ Receipt generation with unique receipt numbers
- ✅ Student details included
- ✅ PDF generation exists
- ⚠️ **Gap:** Receipt date separate from payment date needs verification
- ⚠️ **Gap:** Balance carried forward display needs verification
- ❌ **Missing:** Receipt opens in new window
- ❌ **Missing:** Settings page for document headers/footers

---

## Implementation Priority

### High Priority (Core Functionality Gaps)

1. **Inline Invoice Item Editing** - Click to edit amounts, auto-create credit/debit notes
2. **Payment Sharing** - Share payments among siblings
3. **Transaction Code Uniqueness** - Validate no duplicate transaction codes
4. **Overpayment Handling** - Warning and balance carry forward
5. **Payment Date vs Receipt Date** - Separate fields

### Medium Priority (Enhancements)

6. **Discount Replication** - Replicate discounts across terms & classes
7. **Receipt New Window** - Open receipt in new window with PDF/print options
8. **Document Settings** - Settings page for headers/footers
9. **Sibling Balance Display** - Show all sibling balances in payment form
10. **Invoice Item Dates** - Ensure unique dates based on posting date

### Low Priority (Polish)

11. **Enhanced Change Messages** - Improve "no changes" and change summary messaging
12. **Document Number Settings UI** - Dedicated page for all document number configuration

---

## Next Steps

1. Review this document with stakeholders
2. Prioritize implementation based on business needs
3. Create detailed technical specifications for each gap
4. Implement features in priority order
5. Test thoroughly before deployment

---

**Document Status:** Draft for Review  
**Last Updated:** December 16, 2025

