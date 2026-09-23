# Markdown document catalog (live inventory, 2026-09-22)

Total markdown files (excluding vendor/node_modules): **169**.
Many sprint/execution reports are historical and overlapping.

## `.github/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `.github/pull_request_template.md` | pull_request_template | <!-- Provide a clear description of what this PR does --> | 101 |

## `APPLICATION_FUNCTIONS.md/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `APPLICATION_FUNCTIONS.md` | School Management System — Application Functions | This document describes what the application **does** at the level of **HTTP routes**, **JSON API endpoints**, **Artisan commands**, **domain services**, and the **Expo mobile app* | 460 |

## `CRON_SETUP.md/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `CRON_SETUP.md` | Cron & Supervisor Setup | This document explains how to run Laravel scheduler and queue workers **automatically** on your server, without manually starting them in the terminal. | 40 |

## `README.md/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `README.md` | README | <p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel | 67 |

## `app/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `app/Services/python/README.md` | Bank Statement Parser - Python Dependencies | This directory contains the Python parser for bank statements (MPESA and Equity Bank). | 208 |

## `docs/` (49)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/AUTO_DEPLOY.md` | Automatic deploy from GitHub (no manual SSH) | When you push to `main`, GitHub Actions SSHs into your server and runs `scripts/deploy-production.sh` (Laravel + Next.js website). | 171 |
| `docs/BLADE_PARTIALS_MIGRATION.md` | Blade Partials Migration | Date: 2026-09-09 | 153 |
| `docs/COMPONENT_LIBRARY.md` | Blade Component Library | Phase 2 adds reusable anonymous Blade components under `resources/views/components`. They use the Phase 1 tokens and Bootstrap/AdminLTE markup conventions. Components are currently | 110 |
| `docs/DATABASE_ANALYSIS_REPORT.md` | Production Database Analysis Report | **Generated:** 2026-01-26 | 345 |
| `docs/DEPLOYMENT_INSTRUCTIONS.md` | Production Deployment Instructions | This deployment adds balance brought forward (BBF) tracking to the fee balance report and fixes critical C2B transaction ID conflicts. | 297 |
| `docs/DESIGN_SYSTEM.md` | Web Design System Foundation | This is the shared visual foundation for the Laravel web portal. It is designed to work with the existing Bootstrap 5 and AdminLTE architecture. It does not replace either framewor | 93 |
| `docs/EC2_DEPLOYMENT.md` | EC2 Deployment Guide | - EC2 instance running Ubuntu | 193 |
| `docs/ENABLE_ZIP_EXTENSION.md` | How to Enable PHP Zip Extension | ``` | 72 |
| `docs/FINANCE_CF_REVERSAL_RUNBOOK.md` | FINANCE_CF_REVERSAL_RUNBOOK | The intra-year **"Balance from prior term(s)"** feature has been permanently disabled. | 70 |
| `docs/FIXES_APPLIED_SUMMARY.md` | Transaction Database Fixes - Applied Summary | **Date:** 2026-01-26 | 153 |
| `docs/FIX_PLAN.md` | Database Fix Plan | **Based on Production Database Analysis** | 313 |
| `docs/GOOGLE_SHEETS_FEE_SYNC_DESIGN.md` | Google Sheets ↔ School System Fee Sync – Design | **Yes.** You can build an agent (sync service + optional UI) that: | 146 |
| `docs/HOSTPINNACLE_TICKET_MESSAGE.md` | HostPinnacle Support Ticket - Empty msgId Issue | **Subject:** SMS API Returns Success with Empty msgId Despite Sufficient Account Balance | 147 |
| `docs/IMPLEMENTATION_COMPLETE_SUMMARY.md` | Security & Enhancements Implementation - Complete Summary | - **Files Created:** | 224 |
| `docs/IMPLEMENTATION_SUMMARY.md` | Security & Enhancements Implementation Summary | - Created `PaymentPolicy` with checks for: | 99 |
| `docs/JENGA_INTEGRATION.md` | Jenga (Equity / Finserve) Integration | This project now includes a backend Jenga integration service and secured API routes. | 103 |
| `docs/LESSON_PLAN_SUPERVISION_ROLLOUT.md` | LESSON_PLAN_SUPERVISION_ROLLOUT | - Run migrations (adds submission/rejection fields and timetable linkage to `lesson_plans`). | 35 |
| `docs/MANUAL_TESTING_GUIDE.md` | Manual Testing Guide — Teacher, Senior Teacher (Campus Leader), Super Admin | Use this guide to test the system as **Teacher**, **Senior Teacher / Campus Leader**, and **Super Admin**. Perform each section with the corresponding role. | 200 |
| `docs/NGINX_REPLACE_INSTRUCTIONS.md` | How to Replace the Nginx Default Config (and Fix 413 Upload Error) | Replacing `/etc/nginx/sites-available/default` on the server with a version that includes `client_max_body_size 12M;` so background/logo uploads work. | 70 |
| `docs/PERF_AND_COST_CHANGES_2026-09-16.md` | Performance & AWS cost work — 2026-09-16 | Audit of why the AWS bill was ~$28/month against an expected $7–8, why the | 401 |
| `docs/PHASE5_WORKFLOW_CONSOLIDATION.md` | Phase 5 Workflow Consolidation | Date: 2026-09-09 | 92 |
| `docs/PHASE6_DASHBOARD_FINANCE.md` | Phase 6 Dashboard and Finance IA | Date: 2026-09-09 | 102 |
| `docs/QUEUE_SETUP.md` | Queue setup – SMS, email, WhatsApp without a worker | **This app uses Option A (sync) by default:** receipts and notifications (SMS, email, WhatsApp) run in the same request when payments are created. No queue worker or cron needed. | 91 |
| `docs/QUICK_TEST_CHECKLIST.md` | Quick Test Checklist Before Production | ```bash | 60 |
| `docs/SCHOLARCORE_UI_UX.md` | UI/UX Guideline: School Management System (SMS) — ScholarCore | This document outlines the UI/UX principles, patterns, and component standards for all present and future modules of the School Management System, ensuring a unified user experienc | 98 |
| `docs/SECURITY_AND_ENHANCEMENTS_RECOMMENDATIONS.md` | Security and Enhancement Recommendations for Payment/Transaction System | - Routes are protected by role middleware (`role:Super Admin\|Admin\|Secretary`) | 427 |
| `docs/STUDENT_STATEMENT_SCOPING_VERIFICATION.md` | Student Statement Scoping Verification | This document confirms how the student statement at `/finance/student-statements/{student}` ensures that **all** displayed payments, invoices, credit/debit notes, and totals belong | 101 |
| `docs/SYSTEM_DOCUMENTATION.md` | School Management System - Comprehensive Documentation | **Last Updated:** January 4, 2026 | 1745 |
| `docs/TESTING_GUIDE.md` | Testing Guide for Security & Enhancement Features | ```bash | 310 |
| `docs/TESTSPRITE_QUICK_START.md` | TestSprite Quick Start Checklist | - [ ] Node.js >= 22 installed (`node --version`) | 176 |
| `docs/TESTSPRITE_SETUP_GUIDE.md` | TestSprite Setup and Testing Guide | This guide will help you set up and use TestSprite to test your School Management System. | 205 |
| `docs/TRANSACTION_SYSTEM_ANALYSIS.md` | Transaction System Analysis & Recommendations | **Recommendation: DO NOT DELETE - Refactor and Fix** | 249 |
| `docs/UI_UX_ASSESSMENT.md` | UI/UX Assessment & Premium Redesign Roadmap | **Date:** 2026-09-09 | 195 |
| `docs/USERS_APP_FEATURE_AUDIT.md` | Users App — Functional & Security Inventory (Phase 7A) | **Date:** 2026-09-09 | 444 |
| `docs/USERS_APP_FINAL_UX_AUDIT.md` | Users App — Final UX Audit (Phase 7L) | **Date:** 2026-09-09 | 85 |
| `docs/USERS_APP_QA_MATRIX.md` | Users App — Production QA Matrix | **Status:** Draft for QA execution — **not production-ready declaration** | 126 |
| `docs/WEBSITE_DEPLOYMENT.md` | Public Website Deployment (Next.js) | The Royal Kings **public marketing site** is **not** part of Laravel routing. After `git pull` and migrations, you must also deploy the Next.js app in `website/`. | 96 |
| `docs/WEB_AND_MOBILE_UX_ASSESSMENT.md` | Web Portal and Mobile Apps UX Assessment | Date: 2026-09-09 | 250 |
| `docs/accounting-user-guide.md` | Accounting & Expense Module — User Guide | This guide explains how to use the school finance **accounting system**: chart of accounts, expenses, vouchers, petty cash, fee posting, payroll GL, budgets, and reports. | 261 |
| `docs/demo-customer-guide.md` | Demo Guide for Customers | This guide walks a prospective user through the live demo with full, realistic data. Every account below uses password `Demo@123`. Follow the steps in order to experience how the s | 85 |
| `docs/demo-super-admin-walkthrough.md` | Demo Walkthrough – Super Admin | Use this script to guide stakeholders through the full system using the demo data seeded by `DemoDataSeeder`. | 79 |
| `docs/expense-module-rollout.md` | Expense Module Rollout Guide | 1. Run database migration for new expense tables: | 35 |
| `docs/mobile-app-COMPLETE_SUMMARY.md` | 🎓 School ERP Mobile App - Complete Summary | **Last Updated:** December 22, 2024 | 303 |
| `docs/mobile-app-FINAL_SUMMARY.md` | 🎉 School ERP Mobile App - COMPLETE AND FINAL | A **fully-functional, production-ready React Native Android mobile application** with comprehensive school management features covering 14+ major modules, 180+ API endpoints, and 1 | 285 |
| `docs/mobile-app-PROJECT_SUMMARY.md` | School ERP Mobile App - Project Summary | A production-ready React Native Android mobile application for comprehensive school management, built with TypeScript, Material Design 3, and full Laravel backend integration. | 222 |
| `docs/mobile-app-TESTING_GUIDE.md` | React Native Mobile App - Testing Guide | React Native apps are **native mobile applications** that run on: | 289 |
| `docs/testsprite-SYSTEM_DOCUMENTATION.md` | School Management System - Comprehensive Documentation | **Last Updated:** January 4, 2026 | 1745 |
| `docs/testsprite-mcp-test-report.md` | TestSprite AI Testing Report (MCP) | - **Project Name:** school-management-system2 | 291 |
| `docs/testsprite-raw_report.md` | TestSprite AI Testing Report(MCP) | - **Project Name:** school-management-system2 | 842 |

## `docs/academic-transformation/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/academic-transformation/assessment-engine-design.md` | Unified Assessment Engine — Design Specification | **Version:** 1.0 | 820 |

## `docs/academics/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/academics/01-academics-workspace-audit.md` | 01 — Academics Workspace Audit (Laravel ERP → Admin App) | **Status:** Complete (read-only discovery) | 736 |

## `docs/admin-app/` (7)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/admin-app/01-admin-discovery.md` | 01 — Admin App Discovery (Super Admin Perspective) | - **Per module:** Existing screens · workflows · actions · reports · permissions · pain points · recommended redesign. | 558 |
| `docs/admin-app/02-admin-information-architecture.md` | 02 — Admin App Information Architecture (IA) | 1. **Expose business workflows, not implementation details.** "Billing / Collections / Reconciliation" — not "Voteheads / Posting / Invoices". | 437 |
| `docs/admin-app/03-admin-ui-specifications.md` | 03 — Admin App UI Specifications (Core Frames) | \| Tier \| Width \| Layout model \| | 355 |
| `docs/admin-app/04-device-testing-matrix.md` | 04 — Device Testing Matrix (Post–Sprint 10) | Sprint 10 changed cross-cutting surfaces: | 220 |
| `docs/admin-app/05-post-sprint-10-roadmap.md` | 05 — Post–Sprint 10 Roadmap Decision | \| Area \| Completion \| Notes \| | 134 |
| `docs/admin-app/play-store-publish-guide.md` | Royal Kings Admin — Play Store Release Guide | \| Field \| Value \| | 181 |
| `docs/admin-app/sprint-19-production-hardening-plan.md` | Sprint 19 — Production Hardening Plan | Execution plan for production readiness feedback (June 2026). | 44 |

## `docs/admissions/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/admissions/01-admissions-audit.md` | 01 — Admissions Domain Audit (Laravel ERP) | **Status:** Complete (read-only discovery) | 651 |

## `docs/app-split/` (8)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/app-split/00-README.md` | School ERP — Mobile App Split: Product Architecture Deliverables | \| # \| Document \| What it answers \| | 47 |
| `docs/app-split/01-codebase-audit.md` | STEP 1 — Codebase Audit & Complete Inventory | \| Area \| Detail \| | 240 |
| `docs/app-split/02-feature-matrix.md` | STEP 2 — Feature Matrix (Staff App / Admin App / Shared) | \| Feature \| Code today \| Decision \| Reason \| | 160 |
| `docs/app-split/03-gap-analysis.md` | STEP 3 — Gap Analysis & Modern Feature Recommendations | \| Capability \| Today \| Gap / recommendation \| App \| Priority \| | 108 |
| `docs/app-split/04-architecture.md` | STEP 4 — Target Architecture | **Do not fork the repo twice.** Convert `mobile-app/` into a monorepo so both apps share one API/auth/design layer and the Laravel contract never diverges. | 215 |
| `docs/app-split/05-app-designs.md` | STEP 5 — App Designs (Staff App & Admin App) | **Audience:** Teacher, Senior Teacher, Class Teacher, Subject Teacher, Supervisor, Parent, Guardian, Student, Driver, Transport, and self-service for operational staff. | 242 |
| `docs/app-split/06-ui-specifications.md` | STEP 6 — High-Fidelity UI Specifications (Stitch / Figma ready) | **Layout grid:** 8pt spacing system; screen padding 16; cards radius 16, elevation/shadow per `SHADOWS`. Safe-area aware via `ScreenContainer`. Bottom-tab height + insets respected | 308 |
| `docs/app-split/07-implementation-roadmap.md` | STEP 7 — Implementation Roadmap | **Extract the shared core first, then carve.** We make the existing app the Staff App by (a) lifting reusable code into shared packages, (b) deleting admin surfaces, (c) building t | 135 |

## `docs/design-system-v3/` (14)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/design-system-v3/ANDROID_DESIGN_SYSTEM.md` | Android Design System — ScholarCore Admin V3 | Every screen follows **one design language**. Nothing feels random. The Admin App should feel as cohesive and deliberate as a flagship banking product: consistent spacing, large ro | 112 |
| `docs/design-system-v3/ANIMATION_GUIDE.md` | Animation Guide — Design System V3 | \| Name \| ms \| Use \| | 125 |
| `docs/design-system-v3/COLOR_SYSTEM.md` | Color System — Design System V3 | 1. **Semantic first** — never hardcode hex in screens; use `theme.palette` / `theme.semantic`. | 120 |
| `docs/design-system-v3/COMPONENT_LIBRARY.md` | Component Library — Design System V3 | All components must: use tokens only · support light/dark · expose loading/disabled where interactive · meet 44–48dp targets · pass [DESIGN_REVIEW_CHECKLIST.md](./DESIGN_REVIEW_CHE | 181 |
| `docs/design-system-v3/DESIGN_REVIEW_CHECKLIST.md` | Design Review Checklist — Design System V3 | Copy into the PR description: | 143 |
| `docs/design-system-v3/DESIGN_TOKENS.md` | Design Tokens — Design System V3 | \| Layer \| Location \| | 174 |
| `docs/design-system-v3/ICON_GUIDELINES.md` | Icon Guidelines — Design System V3 | \| Rule \| Spec \| | 79 |
| `docs/design-system-v3/IMPLEMENTATION_STATUS.md` | Implementation Status — Design System V3 | \| Track \| Status \| Visible? \| | 88 |
| `docs/design-system-v3/NAVIGATION_GUIDE.md` | Navigation Guide — Design System V3 | ``` | 123 |
| `docs/design-system-v3/README.md` | ScholarCore Admin App — Design System V3 | V3 evolves Design System V2 into a **premium, flagship-quality** design language: one consistent visual system across Finance, Students, Attendance, Staff, Transport, Communication | 81 |
| `docs/design-system-v3/SCREEN_PATTERNS.md` | Screen Patterns — Design System V3 | Every screen includes as applicable: | 239 |
| `docs/design-system-v3/SPACING_SYSTEM.md` | Spacing System — Design System V3 | Base unit: **4**. All layout spacing must be one of: | 128 |
| `docs/design-system-v3/TYPOGRAPHY.md` | Typography — Design System V3 | 1. **One ramp** — every screen uses named roles below; no ad-hoc `fontSize: 17`. | 114 |
| `docs/design-system-v3/UI_AUDIT.md` | UI Audit — Design System V3 | **Grades:** A = V3-ready · B = V2 strong / needs V3 tokens · C = functional MVP · D = legacy / inconsistent | 362 |

## `docs/execution/` (30)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/execution/admin-app-build-plan.md` | Admin App — Implementation-Ready Build Plan | The Admin App is the **management surface** of the School OS: it **configures, approves, oversees, and reports**. Capture/self-service (mark attendance, enter marks, clock-in, pay  | 692 |
| `docs/execution/admin-app-final-certification-report.md` | Royal Kings ERP Admin App — Final Certification Report | **Date:** 2026-06-05 | 283 |
| `docs/execution/admin-app-testing-guide.md` | Admin App — Testing Guide (Sprints 1–4) | **Purpose:** Verify the Admin mobile app and its Laravel APIs end-to-end before the next sprint. | 213 |
| `docs/execution/api-debugging-report.md` | API Debugging Report — Settings Hub & Student 360 Academics | **Date:** 2026-06-04 | 225 |
| `docs/execution/assessment-phase-0-report.md` | Assessment Engine Phase 0 — Implementation Report | **Date:** 2026-06-04 | 171 |
| `docs/execution/batch-1-report.md` | Sprint 1 · Batch 1 — Admin App Shell — Build Report | A monorepo workspace was bootstrapped inside `mobile-app/` (its package name is already `school-erp-mobile`, the build plan's monorepo root). The existing app stays at the root unt | 134 |
| `docs/execution/batch-2-1-report.md` | Sprint 1 — Batch 2.1 Report: Google Sign-In & Biometrics | **Status:** Complete | 239 |
| `docs/execution/batch-2-report.md` | Sprint 1 — Batch 2 Report: Authentication Foundation | **Status:** Complete | 181 |
| `docs/execution/batch-3-report.md` | Sprint 1 — Batch 3 Report: Permission Foundation | **Status:** Complete | 263 |
| `docs/execution/legacy-feature-parity-report.md` | Legacy App Feature Parity — Admin Mobile | **Date:** 2026-06-04 (updated) | 55 |
| `docs/execution/sprint-13-deferred-completion-report.md` | Sprint 13 — Deferred Items Completion Report | **Date:** 2026-06-04 | 100 |
| `docs/execution/sprint-14-17-completion-report.md` | Sprints 14–17 — Mobile Admin Phase 1 Completion Report | **Date:** 2026-06-05 | 188 |
| `docs/execution/sprint-18-25-completion-report.md` | Sprints 18–25 — Final Completion Phase Report | **Date:** 2026-06-05 | 291 |
| `docs/execution/sprint-2-batch-1-report.md` | Sprint 2 — Batch 1 Report: School Command Center Framework | **Status:** Complete | 185 |
| `docs/execution/sprint-2-batch-2-report.md` | Sprint 2 Batch 2 — School Command Center (Live KPIs) | **Date:** 2026-06-04 | 117 |
| `docs/execution/sprint-2-batch-3-report.md` | Sprint 2 — Batch 3 Report: Approval Center Framework | **Status:** Complete | 187 |
| `docs/execution/sprint-3-batch-1-report.md` | Sprint 3 — Batch 1 Report: Student Registry Foundation | **Status:** Complete | 166 |
| `docs/execution/sprint-3-batch-2-report.md` | Sprint 3 — Batch 2 Report: Student 360 Foundation | **Status:** Complete | 150 |
| `docs/execution/sprint-3-batch-4-report.md` | Sprint 3 — Batch 4 Report: Student 360 Academics | **Status:** Complete | 170 |
| `docs/execution/sprint-4-batch-1-report.md` | Sprint 4 — Batch 1 Report: Settings Hub Foundation | **Status:** Complete | 178 |
| `docs/execution/sprint-4-batch-2-report.md` | Sprint 4 — Batch 2 Report: People Workspace Foundation | **Status:** Complete | 185 |
| `docs/execution/sprint-4-batch-4-report.md` | Sprint 4 — Batch 4 Report: Staff 360 MVP | **Status:** Complete | 261 |
| `docs/execution/sprint-5-batch-1-report.md` | Sprint 5 — Batch 1 Report: Admissions Workspace Foundation | **Status:** Complete | 150 |
| `docs/execution/sprint-5-batch-2-report.md` | Sprint 5 Batch 2 — Online Admissions Mutations & Mobile Enrollment | **Date:** 2026-06-04 | 52 |
| `docs/execution/sprint-6-finance-workspace-report.md` | Sprint 6 — Finance Workspace MVP | **Date:** 2026-06-04 | 254 |
| `docs/execution/sprint-7-academics-workspace-report.md` | Sprint 7 — Academics Workspace MVP | **Date:** 2026-06-04 | 226 |
| `docs/execution/sprint-8-stabilization-report.md` | Sprint 8 — Admin App Stabilization and Completion | **Date:** 2026-06-05 | 227 |
| `docs/execution/sprint-9-12-completion-report.md` | Sprints 9–12 — Backend APIs + Remaining Workspaces | **Date:** 2026-06-05 | 130 |
| `docs/execution/sprint-9-12-regression-test-matrix.md` | Admin App — Full Regression Test Matrix | Use this checklist after deploying backend + mobile (Sprints 8–12). Mark each row: **Pass** / **Fail** / **N/A**. | 179 |
| `docs/execution/student360-academic-audit.md` | Student 360 — Academic Data Source Audit (Sprint 3 Batch 3 Discovery) | **Date:** 2026-06-04 | 415 |

## `docs/finance/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/finance/01-finance-workspace-audit.md` | 01 — Finance Workspace Audit (Laravel ERP → Admin App) | **Status:** Complete (read-only discovery) | 515 |

## `docs/operations/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/operations/01-operations-workspace-audit.md` | 01 — Operations Workspace Audit (Laravel ERP → Admin App) | **Status:** Complete (read-only discovery) | 785 |

## `docs/people/` (2)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/people/01-people-audit.md` | 01 — People Domain Audit (Laravel ERP) | **Status:** Complete (read-only discovery) | 572 |
| `docs/people/staff360-data-audit.md` | Staff 360 — Data Source Audit (Sprint 4 Batch 3 Discovery) | **Status:** Complete (read-only discovery) | 427 |

## `docs/play-store/` (6)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/play-store/impersonation-appeal/01-authorization-letter.md` | 01-authorization-letter | ROYAL KINGS PREMIER SCHOOL LTD | 102 |
| `docs/play-store/impersonation-appeal/02-brand-licence-agreement.md` | 02-brand-licence-agreement | BRAND LICENCE AND DISTRIBUTION AGREEMENT | 100 |
| `docs/play-store/impersonation-appeal/03-play-console-appeal.md` | Play Console appeal / advance notice text | Paste this into the form. Attach the signed PDFs of the authorisation letter and the brand licence. | 45 |
| `docs/play-store/impersonation-appeal/04-store-listing-copy.md` | Safer store listing copy (en-GB) | Update this in Play Console → Grow users → Store presence → Main store listing **before or with** the appeal. You do **not** need to drop the school name. You should make the relat | 68 |
| `docs/play-store/impersonation-appeal/05-signatory-checklist.md` | Signatory checklist | Fill this, then sign. Google looks for matching names across the letter, agreement, Play Console, and public school website. | 42 |
| `docs/play-store/impersonation-appeal/README.md` | Google Play impersonation appeal — Royal Kings Admin | Google removed or blocked the **Royal Kings Admin** store listing because it uses the school’s name, logo, and “official staff app” wording without a **signed** permission document | 69 |

## `docs/prd/` (2)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/prd/01-Epic-Breakdown.md` | 01 — Epic Breakdown & Product Backlog | **Priority** | 552 |
| `docs/prd/02-MASTER-PRODUCT-BACKLOG.md` | 02 — Master Product Backlog (Implementation Blueprint) | The product is **no longer a single-school ERP**. The target is a **commercial multi-tenant SaaS School Operating System (School OS)**. | 714 |

## `docs/review/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/review/admin-app-integration-audit.md` | Admin App Integration Audit | **Status:** Complete (read-only audit) | 856 |

## `docs/saas/` (2)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/saas/CLIENT_LAUNCH_PLAN.md` | Client launch plan — sell the ERP to a second school | **Status:** Ready to execute | 510 |
| `docs/saas/README.md` | Multi-school SaaS + iOS scale-out | Working branch: **`epic/saas-ios-scale`** (keep `main` stable for Royal Kings Play Store hotfixes). | 81 |

## `docs/system-audit/` (11)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/system-audit/01-system-overview.md` | 01 — System Overview | The platform is a **single-school (single-tenant) School ERP** for a Kenyan school operating the **CBC/CBE** curriculum. It manages the full operational lifecycle of the institutio | 168 |
| `docs/system-audit/02-module-inventory.md` | 02 — Module Inventory | - **Purpose:** Bring new learners into the school (online applications + manual entry). | 281 |
| `docs/system-audit/03-database-audit.md` | 03 — Database Audit | `students`, `student_categories`, `student_siblings`, `parent_info`, `families`, `family_update_links`, `family_update_audits`, `family_receipt_links`, `online_admissions`, `studen | 196 |
| `docs/system-audit/04-role-audit.md` | 04 — Role & Permission Audit | `Super Admin`, `Director`, `Admin`, `Secretary`, `Academic Administrator`, `Deputy Senior Teacher`, `Teacher`, `Supervisor`, `Driver`, `Parent`, `Student`, `Accountant`. | 177 |
| `docs/system-audit/05-business-processes.md` | 05 — Business Process Audit | \| Process \| Field \| States \| | 182 |
| `docs/system-audit/06-academic-audit.md` | 06 — Academic System Audit (Kenya CBC/CBE Focus) | \| Function \| Status \| Implementation \| | 105 |
| `docs/system-audit/07-finance-audit.md` | 07 — Finance System Audit | \| Capability \| Status \| Implementation / Notes \| | 126 |
| `docs/system-audit/08-integrations.md` | 08 — Integrations Audit | ```mermaid | 141 |
| `docs/system-audit/09-reporting.md` | 09 — Reporting Audit | \| Component \| Role \| | 120 |
| `docs/system-audit/10-future-state.md` | 10 — Future State Design | - **Multi-tenant SaaS** (today it is single-school): one platform serving many schools/branches, with per-tenant branding, data isolation, and configuration. | 181 |
| `docs/system-audit/MASTER-ERP-AUDIT.md` | MASTER ERP AUDIT — PRD Foundation | \| Phase \| Document \| Question answered \| | 155 |

## `docs/ui/` (8)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `docs/ui/admin-app-design-system-v2.md` | Admin App Design System V2 | **Package:** `@erp/ui` (`mobile-app/packages/ui`) | 333 |
| `docs/ui/admin-app-ui-audit.md` | Admin App UX/UI Audit — Post Design System V2 | Design System V2 delivered meaningful visual uplift where it was applied: **module dashboards** (Dashboard, Finance, Academics, Admissions, People) now have gradient heroes, elevat | 418 |
| `docs/ui/sprint-10-mobile-experience-report.md` | Sprint 10 — Mobile Experience Transformation | **Date:** 2026-06-06 | 106 |
| `docs/ui/sprint-11-15-workspace-completion-report.md` | Sprints 11–15 — Admin App Workspace Completion Report | **Date:** 2026-06-10 | 86 |
| `docs/ui/sprint-16-backend-unblock-report.md` | Sprint 16 — Backend Unblock Phase | This phase closed the "backend-blocked backlog" from the Sprint 11–15 report by adding the | 71 |
| `docs/ui/sprint-17-write-workflows-report.md` | Sprint 17 — Write Workflows Phase | Goal: close out the remaining backend-blocked **write** workflows so every major admin | 97 |
| `docs/ui/sprint-18-final-gaps-report.md` | Sprint 18 — Final Gaps Phase (Library Circulation, GL, Assets, Attachments) | This sprint closes the four remaining gaps identified at the end of Sprint 17: | 127 |
| `docs/ui/sprint-9-premium-ux-report.md` | Sprint 9 — Premium UX Transformation Report | Sprint 9 addressed the audit finding that Design System V2 was implemented at the **dashboard level** but not propagated into registry, 360, and list flows. This sprint focused on  | 210 |

## `frontend/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `frontend/README.md` | Getting Started with Create React App | This project was bootstrapped with [Create React App](https://github.com/facebook/create-react-app). | 71 |

## `mobile-app/` (11)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `mobile-app/EAS_PLAYSTORE.md` | EAS / Play Store builds (Users + Admin) | Never run `eas build` from the **repo root**. That creates the wrong Expo project (`school-management-system2`) and fails versioning. | 53 |
| `mobile-app/README.md` | School ERP Mobile (monorepo) | Supported apps only: | 61 |
| `mobile-app/apps/admin/.expo/README.md` | README | The ".expo" folder is created when an Expo project is started using "expo start" command. | 14 |
| `mobile-app/apps/edulynk/.expo/README.md` | README | The ".expo" folder is created when an Expo project is started using "expo start" command. | 14 |
| `mobile-app/apps/ios/.expo/README.md` | README | The ".expo" folder is created when an Expo project is started using "expo start" command. | 14 |
| `mobile-app/apps/users/.expo/README.md` | README | The ".expo" folder is created when an Expo project is started using "expo start" command. | 14 |
| `mobile-app/apps/users/BACKEND_GAPS.md` | Users App — backend contract gaps | Tracked for `apps/users`. Shells are wired; these items still limit depth or Play Store polish. | 46 |
| `mobile-app/apps/users/CUTOVER.md` | Legacy monorepo root (`mobile-app/src`) — cutover notes | - **Do not ship new features** into root `mobile-app/src` (legacy single-binary multi-role app). | 34 |
| `mobile-app/apps/users/README.md` | Royal Kings Users App | Second Expo binary for **teachers, parents, students, drivers**, and other non-admin staff. | 44 |
| `mobile-app/docs/EAS_UPDATES_AND_APK.md` | OTA updates (EAS Update) and APK downloads | \| What \| How it works \| | 53 |
| `mobile-app/docs/IN_APP_UPDATES_AND_S3_GUIDE.md` | In-app updates and S3 distribution (step-by-step) | This guide explains how staff get **updates inside the app** without reinstalling from scratch every time, and how **Amazon S3** fits in when you are not using the Play Store. | 223 |

## `scripts/` (2)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `scripts/fun_day_2026_trip_report.md` | Fun Day 2026 Trip Report | - Children: **68** (Isaac = one line of **4,500**) | 93 |
| `scripts/fun_day_payment_reconciliation.md` | Fun Day Trip Payment Reconciliation | Fees: **Preschool (Foundation/PP1/PP2) = 2,500** · **Lower primary (G1–G3) = 3,000** | 116 |

## `storage/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `storage/certificates/term2-2026/README.md` | Term 2 2026 Certificates of Excellence | Generated certificates for Royal Kings School awards. | 56 |

## `styles.md/` (1)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `styles.md` | School Management System - Detailed UI/UX Specification | \| Token \| Source Setting Key \| Default \| | 558 |

## `website/` (3)

| File | Title | Summary | Lines |
|---|---|---|---:|
| `website/AGENTS.md` | This is NOT the Next.js you know | <!-- BEGIN:nextjs-agent-rules --> | 6 |
| `website/CLAUDE.md` | CLAUDE | @AGENTS.md | 2 |
| `website/README.md` | or | This is a [Next.js](https://nextjs.org) project bootstrapped with [`create-next-app`](https://nextjs.org/docs/app/api-reference/cli/create-next-app). | 37 |
