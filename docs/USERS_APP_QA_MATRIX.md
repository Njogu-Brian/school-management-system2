# Users App — Production QA Matrix

**Status:** Draft for QA execution — **not production-ready declaration**  
**Date:** 2026-09-09

Fill **Actual** / **Result** during QA. Use severity: Blocker / High / Medium / Low when FAIL.

Legend: **PASS** / **FAIL** / **BLOCKED**

---

## PARENT

| ID | Case | Expected | Actual | Result | Severity |
|----|------|----------|--------|--------|----------|
| P01 | Login with parent credentials | Lands in parent Home | | | |
| P02 | Child selector (multi-child) | Only linked children; selection persists | | | |
| P03 | Home tiles open attendance/fees/diary/homework/results/transport/notifications/settings | Correct screens | | | |
| P04 | Attendance calendar | Shows school days + statuses | | | |
| P05 | Report absence one day | Submitted; school notified; history shows | | | |
| P06 | Report absence multi-day | School days only; weekends skipped | | | |
| P07 | Report absence unrelated student | Denied | | | |
| P08 | Academics / report cards | Only own child published cards | | | |
| P09 | Fees hub | Due/upcoming from real records; invoices/statements/wallet | | | |
| P10 | Payments / statements / invoices | Detail opens; unauthorized student denied | | | |
| P11 | Wallet top-up | STK with valid phone | | | |
| P12 | Transport / live bus | Own child only | | | |
| P13 | Diary Teachers channel | Can open/send for own child | | | |
| P14 | Diary School/Admin channel | Separate from teacher thread | | | |
| P15 | Homework list for child | Visible assigned work | | | |
| P16 | Notifications history | Stored items; unread distinct; mark read keeps history | | | |
| P17 | Profile / settings | Load/save; logout | | | |

---

## TEACHER

| ID | Case | Expected | Actual | Result | Severity |
|----|------|----------|--------|--------|----------|
| T01 | Login teacher | Teacher Home | | | |
| T02 | Home daily work tiles | Attendance, homework, marks, diary, transport, classes, notifications | | | |
| T03 | Mark attendance | Assigned class only | | | |
| T04 | Create homework — all class | Creates; parents of class notified | | | |
| T05 | Create homework — specific students | Only those parents notified | | | |
| T06 | Homework unauthorized class/subject | Denied | | | |
| T07 | Diary teacher channel | Sees assigned students | | | |
| T08 | Diary admin channel API | Forbidden for teacher | | | |
| T09 | Transport / marks / lesson plans | Existing flows work | | | |
| T10 | Notifications | Own history only; unread badge | | | |
| T11 | 08:00 clock reminder (school day) | In-app if not signed in | | | |
| T12 | 09:00 class attendance reminder | In-app for class teachers if unmarked | | | |

---

## STAFF

| ID | Case | Expected | Actual | Result | Severity |
|----|------|----------|--------|--------|----------|
| S01 | Profile view/edit | Self only | | | |
| S02 | Leave apply date range | Valid range accepted; invalid rejected | | | |
| S03 | Leave times | N/A — date-only model (document) | | | |
| S04 | Notifications | Own only | | | |

---

## DUAL ROLE

| ID | Case | Expected | Actual | Result | Severity |
|----|------|----------|--------|--------|----------|
| D01 | Teacher + parent Home/Work switch | Parent shell ↔ teacher shell; cache cleared | | | |
| D02 | Staff + parent | Home parent; Work staff/teacher tools as role allows | | | |
| D03 | Admin/Director + parent Work | Admin app hand-off, not teacher UI | | | |
| D04 | Mode switch with open detail | Navigation resets; no stale child data | | | |
| D05 | App restart with mode persisted | Restores effective mode | | | |
| D06 | Logout/login | No previous account cache/notifications | | | |

---

## PRIVACY

| ID | Case | Expected | Actual | Result | Severity |
|----|------|----------|--------|--------|----------|
| X01 | Parent A cannot access Parent B child APIs | 403 | | | |
| X02 | Parent cannot open unrelated student diary | 403 | | | |
| X03 | Teacher cannot open unrelated class homework create | 403 | | | |
| X04 | Teacher cannot open admin_parent diary | 403 | | | |
| X05 | Admin can open teacher_parent (oversight) | 200 when policy allows | | | |
| X06 | Activity change does not notify unrelated Senior Teacher | No notification | | | |
| X07 | Notification ID of another user | Not readable/deletable | | | |
| X08 | Deep link cannot bypass diary channel ACL | 403 | | | |

---

## PHONE

| ID | Case | Expected | Actual | Result | Severity |
|----|------|----------|--------|--------|----------|
| H01 | Kenya +254 valid local 9 digits | Accepted | | | |
| H02 | Uganda +256 | Accepted with correct length | | | |
| H03 | US +1 10 digits | Accepted | | | |
| H04 | Invalid short local | Error shown | | | |
| H05 | WhatsApp same rules as phone | Consistent | | | |

---

## NOTIFICATIONS

| ID | Case | Expected | Actual | Result | Severity |
|----|------|----------|--------|--------|----------|
| N01 | Stored after event | Visible in history | | | |
| N02 | Unread styling | Distinct | | | |
| N03 | Open → mark read | Count decreases; item remains | | | |
| N04 | Filter unread / all | Correct subsets | | | |
| N05 | Deep link actions | Navigate when typed for role | | | |
| N06 | Recipient correctness (homework/absence/activity) | Only intended users | | | |

---

## Sign-off

| Role | Name | Date | Ready for prod? |
|------|------|------|-----------------|
| QA | | | **NO — matrix draft only** |
| Product | | | |
| Engineering | | | |
