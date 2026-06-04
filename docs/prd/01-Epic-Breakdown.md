# 01 — Epic Breakdown & Product Backlog

> **Source audits:** [`../system-audit/MASTER-ERP-AUDIT.md`](../system-audit/MASTER-ERP-AUDIT.md), [`10-future-state.md`](../system-audit/10-future-state.md), [`06-academic-audit.md`](../system-audit/06-academic-audit.md), [`07-finance-audit.md`](../system-audit/07-finance-audit.md), [`04-role-audit.md`](../system-audit/04-role-audit.md), [`05-business-processes.md`](../system-audit/05-business-processes.md). Mobile design: [`../app-split/`](../app-split/).
> **Authored as:** Product Manager + Enterprise Architect.
> **Structure:** `EPIC → FEATURE → USER STORY → ACCEPTANCE CRITERIA`. No code.

---

## How to read this backlog

**Priority**
| Tag | Meaning | Intent |
|-----|---------|--------|
| **P0** | Critical | Compliance, financial integrity, security, or a hard blocker for everything else |
| **P1** | High | Core value; expected by users this cycle |
| **P2** | Medium | Valuable; schedule after P0/P1 |
| **P3** | Future | Differentiators / nice-to-have |

**Release phases** (from the Master Audit roadmap)
| Phase | Theme |
|-------|-------|
| **R0** | Stabilize & secure |
| **R1** | Foundations (architecture, RBAC, tenancy, shared mobile core) |
| **R2** | Compliance pillars (GL/finance + CBC) |
| **R3** | Engagement & operations |
| **R4** | Intelligence & scale |

**Story IDs:** `E<epic>.<feature>.<story>` (e.g. `E12.2.1`). Acceptance criteria are written as verifiable Given/When/Then or checklists.

---

## Epic portfolio summary

| # | Epic | Priority | Phase | Complexity | Primary dependency |
|---|------|----------|-------|-----------|--------------------|
| 1 | Platform Foundations | P0 | R0–R1 | High | — |
| 2 | Security | P0 | R0 | Medium | E1 |
| 3 | RBAC | P0 | R0–R1 | High | E1, E2 |
| 4 | Multi-Tenancy | P1 | R1 | Very High | E1, E3 |
| 5 | Mobile Ecosystem | P1 | R1–R3 | High | E1, E3 |
| 6 | CBC Assessment Engine | P0 | R2 | High | E7, E8 |
| 7 | Curriculum Management | P1 | R2 | Medium | E1 |
| 8 | Teacher Workspace | P1 | R2–R3 | Medium | E5, E6, E7 |
| 9 | Parent Experience | P1 | R3 | Medium | E5, E11, E17, E22 |
| 10 | Student Experience | P2 | R3 | Medium | E5, E6, E8 |
| 11 | Finance Modernization | P0 | R1–R2 | High | E1, E3 |
| 12 | General Ledger | P0 | R2 | Very High | E11 |
| 13 | Budgeting | P1 | R2–R3 | Medium | E12 |
| 14 | Procurement | P2 | R3 | Medium | E12, E19 |
| 15 | Asset Management | P2 | R3 | Medium | E12 |
| 16 | HR | P1 | R2–R3 | Medium | E3, E12 |
| 17 | Transport | P1 | R3 | High | E5, E22 |
| 18 | Library | P2 | R3 | Low | E3 |
| 19 | Inventory | P2 | R3 | Medium | E3, E14 |
| 20 | Clinic | P2 | R3 | Medium | E3, E22 |
| 21 | Visitor Management | P2 | R3 | Medium | E2, E3 |
| 22 | Communication | P1 | R1–R3 | High | E1, E2 |
| 23 | Analytics | P1 | R4 | High | E11, E12, E6 (data) |
| 24 | AI | P3 | R4 | High | E23, E7, E22 |

---

# EPIC 1 — Platform Foundations
> **Business value:** Removes structural tech debt blocking every other epic; one API contract, derived balances, reliable jobs. **Technical complexity:** High. **Dependencies:** none (enabler). **Release phase:** R0–R1.

### Feature 1.1 — API-first contract & shared client
- **E1.1.1** *As an engineering team, I want a versioned, documented API contract so web and both mobile apps consume one source of truth.*
  - **AC:** OpenAPI spec published for all endpoints; versioning scheme (`/v1`) defined; breaking-change policy documented; web + mobile reference the same contract.
- **E1.1.2** *As a mobile team, I want a shared core SDK (API + auth + types) so Staff and Admin apps don't diverge.* (see `app-split/04-architecture.md`)
  - **AC:** `@erp/core` package wraps all endpoints; both apps import it; no duplicated endpoint definitions.

### Feature 1.2 — Schema consolidation
- **E1.2.1** *As a data architect, I want duplicate/legacy tables retired so there is one source of truth per entity.*
  - **AC:** parent triad (`parent_info`/`families`/`users.parent_id`) consolidated per a documented target; confirmed-dead tables (`classes`, `classroom_subject`, `trip`, `transport`, `staff_meta`) removed after verification; migration squashed to a baseline.
- **E1.2.2** *As a finance owner, I want balances to be derived (event-sourced/recalculated) so denormalized columns can't drift.*
  - **AC:** invoice & wallet balances computed from ledger/allocations; reconciliation job detects drift = 0 on a sample term; `version`/idempotency in place.

### Feature 1.3 — Reliable background processing
- **E1.3.1** *As an operator, I want one authoritative scheduler so no automation silently fails.*
  - **AC:** all schedules in one place; `Kernel.php` vs `routes/console.php` split resolved; each scheduled task has a last-run heartbeat + alert on miss.
- **E1.3.2** *As an operator, I want a managed queue with retries/dead-letter so failed jobs are visible.*
  - **AC:** queue worker supervised; failed jobs surfaced in an admin view; retry/backoff policy defined.

### Feature 1.4 — Environments & observability
- **E1.4.1** *As an SRE, I want logging/metrics/crash reporting so incidents are detected fast.* **(P1)**
  - **AC:** structured logs (no PII), error tracking (Sentry) wired to API + apps, uptime/latency dashboards.

---

# EPIC 2 — Security
> **Business value:** Protects money and PII; prevents webhook forgery and data leakage. **Technical complexity:** Medium. **Dependencies:** E1. **Release phase:** R0.

### Feature 2.1 — Webhook hardening
- **E2.1.1** *As a finance owner, I want payment webhooks authenticated so they cannot be forged.* **(P0)**
  - **AC:** M-Pesa STK/C2B verify IP allowlist + signature/shared-secret; invalid requests rejected & logged; idempotency keys prevent double-processing; success returned only on genuine processing.
- **E2.1.2** *As a security lead, I want SMS DLR and WhatsApp webhooks authenticated.* **(P0)**
  - **AC:** DLR + Wasender endpoints require a secret token; missing/invalid token → 401.

### Feature 2.2 — Secrets & data protection
- **E2.2.1** *As a security lead, I want secrets in a vault/KMS, not on disk/env in plaintext.* **(P1)**
  - **AC:** Jenga private key + gateway secrets stored in KMS/secret manager; access audited.
- **E2.2.2** *As a DPO, I want financial/PII redaction in logs.* **(P0)**
  - **AC:** payment payloads & personal data masked in logs; log access restricted.

### Feature 2.3 — App & transport security
- **E2.3.1** *As a security lead, I want hardened PDF/file handling and TLS controls.* **(P1)**
  - **AC:** DomPDF remote access disabled / HTML sanitized; uploads scanned/typed; TLS enforced; optional certificate pinning on mobile.
- **E2.3.2** *As a security lead, I want a full audit trail for sensitive actions.* **(P1)**
  - **AC:** create/update/delete on finance, RBAC, grades logged with actor, before/after, timestamp; tamper-evident.

---

# EPIC 3 — RBAC
> **Business value:** Enables least-privilege, segregation of duties (critical for finance), and correct mobile/portal behavior. **Technical complexity:** High. **Dependencies:** E1, E2. **Release phase:** R0–R1.

### Feature 3.1 — Canonical role & permission taxonomy
- **E3.1.1** *As an admin, I want one canonical role set seeded idempotently so RBAC is predictable.* **(P0)**
  - **AC:** single source-of-truth seeder; Title-Case only; lowercase duplicates removed; `syncPermissions` ordering deterministic; documented role→permission matrix.
- **E3.1.2** *As an architect, I want permission-first authorization so routes/controllers enforce `permission:` not just `role:`.* **(P0)**
  - **AC:** complete `module.action` catalog; middleware + policies enforce permissions; coarse `role:` only for routing.

### Feature 3.2 — Remove unsafe bypasses
- **E3.2.1** *As a security lead, I want broad Gate bypasses replaced with explicit checks.* **(P0)**
  - **AC:** only a single super-admin bypass remains; `can_access()`/teacher-like bypasses replaced with real permissions; tests assert no unintended escalation.

### Feature 3.3 — Complete role coverage & scoping
- **E3.3.1** *As an HR admin, I want all real school roles available.* **(P1)**
  - **AC:** roles added — Board Member, Principal, Deputy Principal, Head Teacher, Academic Director, Finance Director, Bursar, Receptionist, Transport Manager, Nurse, Librarian, Store Keeper, Security Officer, HR Officer — each with scoped permissions.
- **E3.3.2** *As a senior teacher, I want campus/class-scoped access so I only see my supervised scope.* **(P1)**
  - **AC:** scoping enforced (campus/class) on supervised data; `guardian` modeled as relationship not role; `finance`/`transport` map consistently to Finance Officer/Transport Manager across API + mobile.

### Feature 3.4 — Maker-checker
- **E3.4.1** *As a finance director, I want maker-checker on financial actions so no single user can complete sensitive transactions.* **(P1)**
  - **AC:** payments/vouchers/journals/disbursements require a second approver; configurable per action; enforced server-side.

---

# EPIC 4 — Multi-Tenancy
> **Business value:** Unlocks SaaS / multi-school / multi-branch growth. **Technical complexity:** Very High. **Dependencies:** E1, E3. **Release phase:** R1.

### Feature 4.1 — Tenant model & isolation
- **E4.1.1** *As a platform owner, I want a tenant concept so multiple schools share the platform safely.* **(P1)**
  - **AC:** `tenant_id` (or DB-per-tenant) decision documented; all domain tables tenant-scoped; global query scope prevents cross-tenant reads; tested with 2+ tenants.
- **E4.1.2** *As a school admin, I want per-tenant branding/config so each school looks and behaves like its own.* **(P1)**
  - **AC:** branding/settings/feature-flags per tenant; mobile `/app-branding` returns tenant-correct assets.

### Feature 4.2 — Tenant lifecycle & administration
- **E4.2.1** *As a platform operator, I want to onboard/suspend tenants so commercial operations work.* **(P2)**
  - **AC:** tenant create/seed/suspend; per-tenant plan/tier gating modules; usage metering.
- **E4.2.2** *As a platform operator, I want tenant-aware backups/restore.* **(P2)**
  - **AC:** backup/restore scoped to a tenant; no cross-tenant data in exports.

---

# EPIC 5 — Mobile Ecosystem
> **Business value:** Two focused apps (Staff + Admin) on a shared core; offline-tolerant field capture; engagement. **Technical complexity:** High. **Dependencies:** E1, E3. **Release phase:** R1–R3. **Reference:** [`app-split/`](../app-split/).

### Feature 5.1 — Shared core & design system
- **E5.1.1** *As a mobile team, I want `@erp/core` + `@erp/ui` so both apps reuse API/auth/branding/components.* **(P1)**
  - **AC:** monorepo with shared packages; both apps build from shared core; design tokens single-sourced; per-tenant branding applied.

### Feature 5.2 — Staff App (refactor of existing)
- **E5.2.1** *As a staff user, I want the existing app stripped of admin functions so it is focused and safe.* **(P1)**
  - **AC:** admin shells/screens removed; role shells (teacher/parent/student/driver) intact; no dead nav links; app-mismatch guard redirects admin roles.
- **E5.2.2** *As a teacher/driver, I want offline-first capture so I can work in poor connectivity.* **(P1)**
  - **AC:** attendance/marks/lesson-plan drafts cached + queued; auto-sync on reconnect; pending-sync indicator; idempotent writes.

### Feature 5.3 — Admin App (new)
- **E5.3.1** *As an administrator, I want a dedicated Admin App for management/approvals/dashboards.* **(P1)**
  - **AC:** role-aware dashboards; unified approvals; management flows on shared core; branding wired.

### Feature 5.4 — Push & deep linking
- **E5.4.1** *As a user, I want notifications to open the right screen so alerts are actionable.* **(P1)**
  - **AC:** push payloads carry `{type, route, entityId}`; tap routes to screen; channels per category; invalid tokens pruned; respects preferences/quiet hours.

### Feature 5.5 — Store release
- **E5.5.1** *As a product owner, I want both apps released via OTA pipeline.* **(P2)**
  - **AC:** separate bundle IDs; EAS build/OTA channels; staged rollout; crash-free ≥ 99.5%.

---

# EPIC 6 — CBC Assessment Engine
> **Business value:** Curriculum compliance (KICD/KNEC); competency-first assessment is the product's academic differentiator. **Technical complexity:** High. **Dependencies:** E7, E8. **Release phase:** R2.

### Feature 6.1 — Outcome-level competency model
- **E6.1.1** *As a teacher, I want to assess each sub-strand/outcome by performance level so assessment matches CBC.* **(P0)**
  - **AC:** assessment store keyed by learner × sub-strand/outcome × occasion records a level (**E.E./M.E./A.E./B.E.**) + evidence; not a percentage; correct MoE descriptors.
- **E6.1.2** *As an academic director, I want formative and summative separated so reporting is compliant.* **(P0)**
  - **AC:** single assessment engine with `type = formative|summative|national`; numeric exams are one summative input; `assessments` vs `exams` duplication retired.

### Feature 6.2 — Rubrics & core competencies
- **E6.2.1** *As a teacher, I want rubric grids per sub-strand so marking is standardized.* **(P1)**
  - **AC:** rubrics from curriculum drive marking UI (mobile + web); core competencies assessed per task without manual JSON.

### Feature 6.3 — CBC report card
- **E6.3.1** *As a parent, I want an official-format CBC report so I understand my child's competency progress.* **(P0)**
  - **AC:** report layout = learning areas → strands → competencies → teacher narrative; summative results appended; portfolio summary included.

### Feature 6.4 — KNEC / national assessment
- **E6.4.1** *As an exams officer, I want to capture & export national assessment data.* **(P1)**
  - **AC:** KPSEA/KJSEA capture; export pack; cohort reporting; KNEC assessment number validated.

---

# EPIC 7 — Curriculum Management
> **Business value:** Authoritative KICD curriculum, schemes & coverage tracking. **Technical complexity:** Medium. **Dependencies:** E1. **Release phase:** R2.

### Feature 7.1 — Curriculum library
- **E7.1.1** *As an academic admin, I want a verified curriculum tree (learning areas/strands/sub-strands/competencies).* **(P1)**
  - **AC:** curriculum tree per grade/learning area; LLM-assisted + human-verified ingestion replaces regex; fidelity to KICD designs confirmed by reviewer.

### Feature 7.2 — Schemes of work & coverage
- **E7.2.1** *As a teacher, I want schemes generated and tracked against delivery so coverage is visible.* **(P1)**
  - **AC:** scheme generated from curriculum; week-by-week breakdown; delivery vs plan coverage %; "behind schedule" flag.
- **E7.2.2** *As an academic director, I want school-wide curriculum coverage reports.* **(P2)**
  - **AC:** coverage % by class/subject/teacher vs `cbc_substrands`; export.

### Feature 7.3 — Lesson plans
- **E7.3.1** *As a teacher, I want to author, submit, and get lesson plans approved (web + mobile parity).* **(P1)**
  - **AC:** draft→submitted→approved/rejected on both web and mobile; templates; clone previous term; reviewer notifications.

---

# EPIC 8 — Teacher Workspace
> **Business value:** Mobile-first teaching cockpit drives adoption & data quality. **Technical complexity:** Medium. **Dependencies:** E5, E6, E7. **Release phase:** R2–R3.

### Feature 8.1 — Daily cockpit
- **E8.1.1** *As a teacher, I want a home with next class, pending tasks, and quick actions.* **(P1)**
  - **AC:** next-class card, attendance-due/marks-due counts, clock-in chip, quick actions; pull-to-refresh; offline cache.

### Feature 8.2 — Attendance & marks capture
- **E8.2.1** *As a class teacher, I want fast offline attendance ("all present" + exceptions).* **(P1)**
  - **AC:** bulk present, per-student toggle + reason, queued offline, parents of absentees notified on sync.
- **E8.2.2** *As a subject teacher, I want matrix marks entry with validation & autosave.* **(P1)**
  - **AC:** grid entry, max-marks validation, autosave per cell, batch submit, offline queue.

### Feature 8.3 — Gradebook & homework
- **E8.3.1** *As a teacher, I want a continuous gradebook so I can track running performance.* **(P2)**
  - **AC:** weighted categories, running averages, per-student trend, export.
- **E8.3.2** *As a teacher, I want homework with student submissions and grading.* **(P2)**
  - **AC:** assign with attachment + due date; students submit; grade; due reminders; parent visibility.

### Feature 8.4 — Self-service & supervision
- **E8.4.1** *As a staff member, I want clock-in/out, leave, payslips, and requisitions on mobile.* **(P1)**
  - **AC:** GPS geofenced clock; apply leave + status; payslip download; raise requisition.
- **E8.4.2** *As a senior teacher, I want supervised reviews (lesson plans, classes, staff, fee balances).* **(P2)**
  - **AC:** scoped review queues; approve/reject with reason; supervised lists read-only where appropriate.

---

# EPIC 9 — Parent Experience
> **Business value:** Engagement, fee collection, satisfaction, safety. **Technical complexity:** Medium. **Dependencies:** E5, E11, E17, E22. **Release phase:** R3.

### Feature 9.1 — Per-child dashboard
- **E9.1.1** *As a parent, I want a per-child overview (attendance, results, fees, transport).* **(P1)**
  - **AC:** child switcher; per-child cards; latest result; next event; offline cache; only my children visible.

### Feature 9.2 — Financial portal
- **E9.2.1** *As a parent, I want to view balances and pay fees per child.* **(P1)**
  - **AC:** balances, invoices, statement, "Pay now" (M-Pesa STK), receipt download, history; consolidated multi-child view; payment plans visible.

### Feature 9.3 — Engagement
- **E9.3.1** *As a parent, I want to chat with teachers and receive circulars/announcements.* **(P1)**
  - **AC:** 1:1 chat with assigned teachers; announcements/circulars with read receipts; report-card access; permission-slip e-sign.
- **E9.3.2** *As a parent, I want live bus tracking and pickup confirmation.* **(P1)** (depends E17)
  - **AC:** live map + ETA; arrival alerts; boarded/alighted notifications; pickup verification.

---

# EPIC 10 — Student Experience
> **Business value:** Learner ownership of academics; future-proofing. **Technical complexity:** Medium. **Dependencies:** E5, E6, E8. **Release phase:** R3.

### Feature 10.1 — Student home & academics
- **E10.1.1** *As a student, I want my timetable, homework, and results.* **(P2)**
  - **AC:** today's timetable; homework due + submit; published results; attendance streak; offline cache.

### Feature 10.2 — Resources & library
- **E10.2.1** *As a student, I want learning resources and my borrowings.* **(P3)**
  - **AC:** resource library per subject/grade with offline download; "my borrowings" with due dates.

---

# EPIC 11 — Finance Modernization
> **Business value:** Single source of truth for money; reliable collection & reconciliation. **Technical complexity:** High. **Dependencies:** E1, E3. **Release phase:** R1–R2.

### Feature 11.1 — Unified transactions & allocation
- **E11.1.1** *As an accountant, I want one transactions model across channels so reconciliation is simple.* **(P1)**
  - **AC:** `payments`/`payment_transactions`/`mpesa_c2b`/`bank_statement` unified with a `channel` discriminator; relational allocations replace JSON splits; sibling sharing preserved.
- **E11.1.2** *As an accountant, I want automated reconciliation with smart matching.* **(P1)**
  - **AC:** auto-match by admission/invoice/phone/name + learned matches; unmatched queue; confirm/reject/split; audit trail.

### Feature 11.2 — Fee lifecycle completeness
- **E11.2.1** *As a finance officer, I want hostel/mess fees posted to invoices.* **(P1)**
  - **AC:** hostel/mess rate cards generate invoice items via posting; balances reflect them.
- **E11.2.2** *As an accountant, I want refunds, including M-Pesa.* **(P1)**
  - **AC:** refund workflow with approval; M-Pesa refund implemented; ledger + statement reflect refund.

### Feature 11.3 — Parent-facing collection
- **E11.3.1** *As a parent, I want reliable STK payment with receipt.* **(P0)** (with E2.1)
  - **AC:** STK initiate → verified callback → receipt → statement update; failure states surfaced; idempotent.

---

# EPIC 12 — General Ledger
> **Business value:** Real accounting → statutory statements, audit, board confidence. **Technical complexity:** Very High. **Dependencies:** E11. **Release phase:** R2.

### Feature 12.1 — Chart of accounts & journals
- **E12.1.1** *As a finance director, I want a chart of accounts and balanced journal entries.* **(P0)**
  - **AC:** COA configurable; `journal_entries`/`journal_lines` enforce debits = credits; posted entries immutable; full audit.

### Feature 12.2 — Auto-posting from subledgers
- **E12.2.1** *As an accountant, I want fees/payments/expenses/payroll/bank to auto-post to the GL.* **(P0)**
  - **AC:** double-entry postings generated from each subledger event; idempotent; reversible via contra entries; mapping table COA↔events.

### Feature 12.3 — Financial statements & close
- **E12.3.1** *As a finance director, I want trial balance, P&L, balance sheet, cash flow.* **(P0)**
  - **AC:** statements generated from GL for any period; tie to subledgers; export PDF/Excel.
- **E12.3.2** *As a finance director, I want period close/lock.* **(P1)**
  - **AC:** close locks postings for a period; reopen is permissioned + audited; prevents retroactive drift.

### Feature 12.4 — Petty cash & multi-currency
- **E12.4.1** *As a bursar, I want petty cash management.* **(P2)**
  - **AC:** float, top-up, vouchers, reconciliation, GL posting.
- **E12.4.2** *As a finance director, I want multi-currency support.* **(P3)**
  - **AC:** currency per transaction; FX rates; reporting currency conversion.

---

# EPIC 13 — Budgeting
> **Business value:** Financial planning, cost control, board accountability. **Technical complexity:** Medium. **Dependencies:** E12. **Release phase:** R2–R3.

### Feature 13.1 — Budgets & variance
- **E13.1.1** *As a finance director, I want budgets per COA/department.* **(P1)**
  - **AC:** budget lines per account/department/period; versioning; approval.
- **E13.1.2** *As a manager, I want budget vs actual reporting.* **(P1)**
  - **AC:** real-time variance from GL; drill-down; alerts on overspend.

### Feature 13.2 — Commitment control
- **E13.2.1** *As a finance officer, I want encumbrance so requisitions/POs reserve budget.* **(P2)** (with E14)
  - **AC:** approved requisitions/POs reduce available budget; warns/blocks over-budget per policy.

---

# EPIC 14 — Procurement
> **Business value:** Controlled spending, vendor management, audit. **Technical complexity:** Medium. **Dependencies:** E12, E19. **Release phase:** R3.

### Feature 14.1 — Requisition → PO → GRN
- **E14.1.1** *As staff, I want to raise a requisition that flows to approval and purchase order.* **(P2)**
  - **AC:** requisition → approval → PO to vendor → goods receipt note → stock update; statuses tracked; GL/budget integration.
- **E14.1.2** *As a procurement officer, I want vendor management.* **(P2)**
  - **AC:** vendor records, terms, performance, linkage to expenses/POs.

### Feature 14.2 — Three-way match
- **E14.2.1** *As an accountant, I want PO/GRN/invoice matching before payment.* **(P2)**
  - **AC:** payment blocked unless PO, GRN, and invoice reconcile within tolerance; exceptions routed to approval.

---

# EPIC 15 — Asset Management
> **Business value:** Fixed-asset accountability, depreciation, insurance/audit. **Technical complexity:** Medium. **Dependencies:** E12. **Release phase:** R3.

### Feature 15.1 — Asset register
- **E15.1.1** *As a bursar, I want a fixed-asset register.* **(P2)**
  - **AC:** asset records (category, location, custodian, value, acquisition); tagging/QR; transfers; disposals.

### Feature 15.2 — Depreciation
- **E15.2.1** *As an accountant, I want automated depreciation posting to the GL.* **(P2)**
  - **AC:** depreciation schedules (method per category); periodic posting; net book value reporting.

### Feature 15.3 — Maintenance & verification
- **E15.3.1** *As an operations manager, I want maintenance logs and physical verification.* **(P3)**
  - **AC:** maintenance schedule + history; periodic stock-take with variance report.

---

# EPIC 16 — HR
> **Business value:** Staff lifecycle, payroll integrity, performance, compliance. **Technical complexity:** Medium. **Dependencies:** E3, E12. **Release phase:** R2–R3.

### Feature 16.1 — Payroll → GL & statutory
- **E16.1.1** *As a finance director, I want payroll to post to the GL with statutory liabilities.* **(P1)**
  - **AC:** payroll run posts salary expense + PAYE/NSSF/NHIF liabilities to GL; remittance tracking; payslips with YTD + statutory.

### Feature 16.2 — Leave & advances
- **E16.2.1** *As HR, I want leave balances, calendar, and cover assignment.* **(P1)**
  - **AC:** accrual policy; balances; who's-out calendar; multi-level approval; cover assignment.
- **E16.2.2** *As staff, I want advances/loans with payroll recovery.* **(P2)**
  - **AC:** request → approve → disburse → recover via deductions; balance tracking.

### Feature 16.3 — Performance & onboarding
- **E16.3.1** *As HR, I want appraisal cycles (TPAD-aligned).* **(P2)**
  - **AC:** self → supervisor → moderation; goals; evidence; scoring; reports.
- **E16.3.2** *As HR, I want recruitment/onboarding checklists.* **(P3)**
  - **AC:** onboarding tasks, document collection, contract e-sign.

---

# EPIC 17 — Transport
> **Business value:** Safety differentiator; parent trust; operational control. **Technical complexity:** High. **Dependencies:** E5, E22. **Release phase:** R3.

### Feature 17.1 — Live tracking
- **E17.1.1** *As a driver, I want background GPS broadcast during trips.* **(P1)**
  - **AC:** trip start/stop; background location with battery/policy compliance; telemetry buffered offline.
- **E17.1.2** *As a parent, I want a live map with ETA and arrival alerts.* **(P1)**
  - **AC:** real-time bus position; ETA; geofence arrival notifications; only for my child's route.

### Feature 17.2 — Verified pickup
- **E17.2.1** *As a school, I want QR/OTP pickup verification so handover is secure.* **(P1)**
  - **AC:** per-stop boarding/alighting scan; guardian verification (QR/OTP); unauthorized-pickup alert; parent boarded/alighted notification.

### Feature 17.3 — Fleet & routes
- **E17.3.1** *As a transport manager, I want map-based route/vehicle management and utilization.* **(P2)**
  - **AC:** route/drop-point/vehicle CRUD with map; capacity vs assignment; utilization analytics; transport fee integration.

---

# EPIC 18 — Library
> **Business value:** Resource stewardship; learner self-service. **Technical complexity:** Low. **Dependencies:** E3. **Release phase:** R3.

### Feature 18.1 — Circulation
- **E18.1.1** *As a librarian, I want catalog, cards, and circulation with overdue automation.* **(P2)**
  - **AC:** book/copy/card CRUD; borrow/return/renew/mark-lost; overdue auto-reminders + fines; Librarian role enforced.

### Feature 18.2 — Self-service & analytics
- **E18.2.1** *As a student/parent, I want to browse catalog and see my borrowings.* **(P2)**
  - **AC:** catalog search; "my borrowings"; reservations.
- **E18.2.2** *As a librarian, I want utilization & overdue analytics.* **(P3)**
  - **AC:** circulation stats, popular titles, overdue trends.

---

# EPIC 19 — Inventory
> **Business value:** Stock accuracy, consumption control, school shop. **Technical complexity:** Medium. **Dependencies:** E3, E14. **Release phase:** R3.

### Feature 19.1 — Stock control
- **E19.1.1** *As a store keeper, I want item stock with adjustments and movements.* **(P2)**
  - **AC:** items, stock levels, adjustments, transactions in/out; Store Keeper role; low-stock alerts.
- **E19.1.2** *As a store keeper, I want valuation & consumption reports.* **(P2)**
  - **AC:** stock valuation (FIFO/avg), consumption by period/department, reorder report.

### Feature 19.2 — Student requirements & POS
- **E19.2.1** *As a class teacher, I want to collect student requirements against templates.* **(P2)**
  - **AC:** requirement templates per class/term; collection capture; status per student.
- **E19.2.2** *As a shop operator, I want POS with public shop + uniforms integrated to finance.* **(P2)**
  - **AC:** products/variants/orders; public token shop; uniform backorders; payment + receipt; revenue to GL.

---

# EPIC 20 — Clinic
> **Business value:** Learner welfare, duty of care, parent trust. **Technical complexity:** Medium. **Dependencies:** E3, E22. **Release phase:** R3.

### Feature 20.1 — Clinic visits & records
- **E20.1.1** *As a nurse, I want to log clinic visits and treatments.* **(P2)**
  - **AC:** visit (symptoms, treatment, medication), parent notification, recurring-condition flag; Nurse role.
- **E20.1.2** *As a nurse, I want per-student medical profiles.* **(P2)**
  - **AC:** allergies, immunizations, conditions, emergency contacts; parent can maintain via portal; access controlled.

### Feature 20.2 — Medication & alerts
- **E20.2.1** *As a nurse, I want scheduled medication administration logs.* **(P3)**
  - **AC:** med schedule, administration log, reminders, audit.

---

# EPIC 21 — Visitor Management
> **Business value:** Campus security, compliance, front-desk efficiency. **Technical complexity:** Medium. **Dependencies:** E2, E3. **Release phase:** R3.

### Feature 21.1 — Visitor check-in/out
- **E21.1.1** *As a receptionist/security officer, I want visitor check-in/out with host notification.* **(P2)**
  - **AC:** pre-registration; check-in with photo/ID; QR badge; host notified; check-out; blacklist; Security Officer/Receptionist roles.

### Feature 21.2 — Gate pass & incidents
- **E21.2.1** *As security, I want student/staff gate passes and incident reporting.* **(P2)**
  - **AC:** exit-pass approval workflow; incident capture (photos, severity, assignment, resolution); audit trail.

---

# EPIC 22 — Communication
> **Business value:** Engagement, timely alerts, reduced manual messaging. **Technical complexity:** High. **Dependencies:** E1, E2. **Release phase:** R1–R3.

### Feature 22.1 — Real-time chat
- **E22.1.1** *As a parent/teacher, I want real-time 1:1 and group chat.* **(P1)**
  - **AC:** chat with read receipts, attachments, moderation; Pusher/Echo (or equivalent) wired; per-tenant; abuse controls.

### Feature 22.2 — Announcements & circulars
- **E22.2.1** *As an admin, I want targeted, scheduled announcements/circulars with read receipts.* **(P1)**
  - **AC:** target by role/class/grade; schedule; attachments; acknowledgment tracking; pinned.

### Feature 22.3 — Multi-channel orchestration
- **E22.3.1** *As an admin, I want unified SMS/WhatsApp/email/push with delivery tracking and preferences.* **(P1)**
  - **AC:** one composer → channels; templates/placeholders; delivery reports; opt-outs/quiet hours; credit-aware; `sms_logs` vs `communication_logs` consolidated.

---

# EPIC 23 — Analytics
> **Business value:** Data-driven leadership; board confidence; early-warning. **Technical complexity:** High. **Dependencies:** E11, E12, academic data (E6). **Release phase:** R4.

### Feature 23.1 — Data platform
- **E23.1.1** *As a data team, I want a warehouse fed by domain events.* **(P1)**
  - **AC:** event bus → warehouse; conformed dimensions (student, term, class, account); tenant-scoped.

### Feature 23.2 — Role dashboards & board pack
- **E23.2.1** *As a board member/principal, I want executive dashboards & a board pack.* **(P1)**
  - **AC:** enrollment & retention trends, fee-collection rate + forecast, academic performance trends, attendance/turnover, risk register; exportable PDF.
- **E23.2.2** *As an academic director, I want CBC compliance analytics.* **(P2)**
  - **AC:** curriculum coverage %, performance-level distribution by strand, portfolio completeness.

### Feature 23.3 — Self-service & forecasting
- **E23.3.1** *As an analyst, I want a self-service report builder with scheduled delivery.* **(P2)**
  - **AC:** saved filters, scheduled email, Excel/PDF; promote API-only reports (trends/insights/mastery) to web + mobile.
- **E23.3.2** *As a finance director, I want fee-collection forecasting.* **(P2)**
  - **AC:** statistical forecast by term/class/cohort; confidence; variance to plan.

---

# EPIC 24 — AI
> **Business value:** Differentiation, efficiency, early intervention. **Technical complexity:** High. **Dependencies:** E23, E7, E22. **Release phase:** R4. **Governance:** data-privacy policy, local-model option, consent, PII redaction.

### Feature 24.1 — Curriculum & content intelligence
- **E24.1.1** *As a teacher, I want AI-assisted scheme/lesson/assessment generation.* **(P3)**
  - **AC:** RAG over verified curriculum; teacher reviews/edits before save; rubric suggestions; audit of AI usage.

### Feature 24.2 — Finance & operations AI
- **E24.2.1** *As an accountant, I want ML-assisted payment matching.* **(P3)**
  - **AC:** auto-reconcile high-confidence matches; human review for low-confidence; learns from corrections.
- **E24.2.2** *As leadership, I want early-warning on at-risk learners.* **(P3)**
  - **AC:** risk scores from attendance/performance/fees; explainable; triggers tasks/notifications.

### Feature 24.3 — Assistants & drafting
- **E24.3.1** *As an admin, I want AI-drafted communications with translation (EN/SW).* **(P3)**
  - **AC:** generate announcements/reminders; tone/translation; human approval before send.
- **E24.3.2** *As an admin, I want natural-language analytics queries.* **(P3)**
  - **AC:** "show fee collection vs last term" returns governed, tenant-scoped results; no PII leakage.

---

## Release plan rollup

| Phase | Epics (primary work) |
|-------|----------------------|
| **R0 — Stabilize & secure** | E2 (Security), E3 (RBAC canonical + bypass removal), E1 (scheduler/observability) |
| **R1 — Foundations** | E1 (schema/balances/API), E3 (scoping/maker-checker), E4 (Multi-tenancy), E5 (shared core + Staff App), E11 (unified transactions), E22 (multi-channel) |
| **R2 — Compliance pillars** | E12 (GL), E11 (fee completeness), E6 (CBC), E7 (Curriculum), E13 (Budgeting), E16 (Payroll→GL), E8 (Teacher Workspace), E5 (Admin App) |
| **R3 — Engagement & ops** | E9 (Parent), E10 (Student), E17 (Transport), E14 (Procurement), E15 (Assets), E18 (Library), E19 (Inventory), E20 (Clinic), E21 (Visitor), E22 (chat/circulars), E5 (store release) |
| **R4 — Intelligence & scale** | E23 (Analytics), E24 (AI), E4 (multi-tenant GA), residual P3s |

## Priority rollup
- **P0 (Critical):** E1 (foundations), E2 (webhooks/PII), E3 (canonical RBAC + bypass), E6 (CBC core + report), E11.3 (parent STK), E12 (GL core + statements).
- **P1 (High):** E4, E5, E7, E8, E9, E11, E13, E16, E17, E22, E23 (data + dashboards).
- **P2 (Medium):** E10, E12.4 (petty cash), E14, E15, E16.2/16.3, E18, E19, E20, E21, E23.2/23.3.
- **P3 (Future):** E10.2, E12.4.2 (multi-currency), E15.3, E16.3.2, E18.2.2, E20.2, E24 (all).

> **Next PRD artifacts:** `02-Release-Plan.md` (sprint/quarter mapping + capacity), `03-NFRs.md` (performance/security/availability targets), `04-Data-Migration-Plan.md` (schema consolidation + balance backfill). This epic breakdown is the backlog spine those documents draw from.
