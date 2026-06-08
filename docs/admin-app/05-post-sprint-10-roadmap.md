# 05 — Post–Sprint 10 Roadmap Decision

> **Status:** Recommended direction as of 2026-06-06  
> **Context:** After Sprints 7–10, the Admin App crossed from *functional ERP* to *modern mobile school operations platform*. Module breadth is sufficient; UX quality and adoption drivers matter more than adding surface area.

---

## Completion snapshot (post–Sprint 10)

| Area | Completion | Notes |
|------|------------|-------|
| Students | 92% | Registry + 360 strong |
| Student 360 | 90% | Collapsing header in Sprint 10 |
| Admissions | 90% | Pipeline + filter sheets |
| Finance | 85% | Read-only cockpit; reconciliation |
| Academics | 85% | Exams/marks; CBC deferred |
| People / Staff 360 | 85–88% | Registry + 360 |
| Dashboard | 82% | KPI trends added |
| Approvals | 80% | Inbox + filters |
| Settings | 75% | Card hub redesign |
| UX / Design | 85% | V2 + Sprint 10 mobile UX |
| Operations | ~0% (workspace) | Backend partial; mobile placeholder-heavy |
| Communication | ~40% (workspace) | SMS/announcements screens exist; not full hub |

---

## Immediate next step: Device Testing (not development)

**Before any Sprint 11 code**, run the [Device Testing Matrix](./04-device-testing-matrix.md).

Sprint 10 touched navigation, search, filters, headers, and layouts — the highest regression-risk category. Gate Sprint 11 on:

1. Cross-cutting regression (12 checks)
2. P0 role journeys (Director, Principal, Secretary, Accountant)
3. Zero open P0 defects

---

## Sprint 11 fork: Operations vs Communication

### Option A — Operations (better product completeness)

Build **Operations Phase 1** only — what already exists on the backend:

```text
Operations
├── Dashboard     (trips today, routes, medical flags, requirements — existing APIs)
├── Transport     (routes, trips, vehicles — read-only first)
├── Clinic        (health flags, conditions, emergency contacts — read-only)
└── Requirements  (birth cert, transfer letter, KCPE/KPSEA, parent ID — completion status)
```

**Do not build yet:** Inventory, Assets, Visitor Management, Security (backend projects first).

**Audit reality:** ~22% immediately buildable, ~78% blocked without new APIs.

**Best for:** Schools already using transport/clinic modules; internal product quality bar.

---

### Option B — Communication (faster commercial adoption) ⭐ Recommended

Build **Communication Workspace** as Sprint 11.

**Why:**

| Factor | Communication | Operations |
|--------|---------------|------------|
| Audience | Every school (director, secretary, teachers) | Transport manager, nurse, storekeeper |
| Demo value | “Send SMS / WhatsApp / Broadcast” — instant | “Route 4, KDG 123A” — narrow |
| Existing foundation | Push ✅, SMS ✅, WhatsApp hooks ✅, student/staff/parent data ✅ | Transport API partial; clinic/requirements scattered |
| Placeholder risk | Lower — composer + history patterns exist | Higher — 78% blocked in original audit |

**Existing mobile assets (admin app):**

- `CommunicationDashboardScreen`, `SmsComposeScreen`, `SmsHistoryScreen`
- `AnnouncementsListScreen`, `TemplatesListScreen`
- Push notifications + deep links (Sprint 8+)

**Sprint 11 Communication scope (proposed):**

```text
Communication Workspace
├── Dashboard        (recent sends, delivery stats, quick actions)
├── Broadcast        (SMS + push; segment by class/grade/parent)
├── Announcements    (polish existing list/detail/form)
├── Templates        (polish existing)
├── WhatsApp         (if API ready; else stub with honest empty state)
└── Message history  (unified SMS + announcement log)
```

---

## Recommended roadmap (commercial adoption)

```text
Device Testing     → Gate (no sprint number — 3–5 days on device)
Sprint 11          → Communication Workspace
Sprint 12          → Operations Phase 1 (Transport, Clinic, Requirements)
Sprint 13          → Reports & Analytics
Sprint 14          → CBC Transformation
Sprint 15          → Accounting Transformation
```

### Alternative (product completeness first)

```text
Device Testing     → Gate
Sprint 11          → Operations Phase 1
Sprint 12          → Communication Workspace
Sprint 13+         → unchanged
```

---

## Decision record

| Question | Recommendation |
|----------|----------------|
| Build before device test? | **No** — test Sprint 10 first |
| Sprint 11 default? | **Communication** (adoption) |
| When Operations? | Sprint 12 Phase 1 only (no inventory/assets/visitors) |
| When Reports? | Sprint 13 — after daily-use workspaces stable |

**Decision owner:** _______________ **Date:** _______________

---

## References

- [01 — Admin Discovery](./01-admin-discovery.md) — Operations 22% / Communication gaps
- [Sprint 10 Mobile Experience Report](../ui/sprint-10-mobile-experience-report.md)
- [Device Testing Matrix](./04-device-testing-matrix.md)
