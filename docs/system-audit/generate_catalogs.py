#!/usr/bin/env python3
"""Generate human-readable catalogs + verified redundancy report from inventory JSON."""
from __future__ import annotations

import json
import re
from collections import Counter, defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
INV = json.loads((ROOT / "docs/system-audit/_inventory.json").read_text(encoding="utf-8"))
OUT = ROOT / "docs/system-audit"

# --- helpers ---

PURPOSE_HINTS = [
    (r"\bindex\b", "list/index page"),
    (r"\bcreate\b", "create form"),
    (r"\bedit\b", "edit form"),
    (r"\bshow\b", "detail view"),
    (r"\bform\b|_form\b", "shared form partial"),
    (r"\bpdf\b", "PDF template"),
    (r"\bprint\b", "print layout"),
    (r"\bimport\b", "import workflow"),
    (r"\bexport\b", "export layout"),
    (r"dashboard\b", "dashboard"),
    (r"partials?\b", "partial/include"),
    (r"layout", "layout wrapper"),
    (r"mail|email", "email template"),
    (r"public\b", "public/unauthenticated page"),
]


def purpose_from_name(name: str) -> str:
    n = name.replace("-", "_").replace(".", " ")
    for pat, label in PURPOSE_HINTS:
        if re.search(pat, n):
            return label
    parts = name.split(".")
    return parts[-1].replace("_", " ").replace("-", " ")


def purpose_controller(cls: str, folder: str, methods: list[str]) -> str:
    base = re.sub(r"Controller$", "", cls)
    base = re.sub(r"(?<!^)(?=[A-Z])", " ", base)
    area = folder if folder != "root" else "core"
    mset = set(methods)
    if {"index", "create", "store", "show", "edit", "update", "destroy"} <= mset:
        kind = "full resource CRUD"
    elif {"index", "store", "update", "destroy"} & mset:
        kind = "resource-style controller"
    elif any(x.startswith("index") or x == "show" for x in methods) and len(methods) <= 3:
        kind = "read-heavy controller"
    elif cls.startswith("Api"):
        kind = "JSON API controller"
    else:
        kind = "action controller"
    return f"{area}: {base} — {kind}"


def purpose_model(cls: str, folder: str, table: str) -> str:
    area = folder if folder != "root" else "core"
    nice = re.sub(r"(?<!^)(?=[A-Z])", " ", cls)
    return f"{area} entity `{nice}` (table `{table}`)"


def md_escape(s: str) -> str:
    return s.replace("|", "\\|")


# Verify unused blades against extra patterns (dynamic includes, x- tags, Laravel conventions)
framework_used = set()
for b in INV["unused_blades"]:
    n = b["name"]
    if n.startswith("errors.") or n.startswith("vendor.") or n.startswith("auth."):
        framework_used.add(n)
    if n.startswith("components."):
        framework_used.add(n)  # design-system components may be unused OR used as <x-...> with aliases

# Scan for more include styles
extra_refs = set()
view_dir = ROOT / "resources" / "views"
php_roots = [ROOT / "app", ROOT / "routes", ROOT / "resources" / "views"]
patterns = [
    re.compile(r"@include\(\s*['\"]([^'\"]+)['\"]"),
    re.compile(r"@includeIf\(\s*['\"]([^'\"]+)['\"]"),
    re.compile(r"@extends\(\s*['\"]([^'\"]+)['\"]"),
    re.compile(r"view\(\s*['\"]([^'\"]+)['\"]"),
    re.compile(r"loadView\(\s*['\"]([^'\"]+)['\"]"),
    re.compile(r"<x-([a-zA-Z0-9._-]+)"),
    re.compile(r"@component\(\s*['\"]([^'\"]+)['\"]"),
    re.compile(r"markdown\(\s*['\"]([^'\"]+)['\"]"),
    re.compile(r"Blade::renderComponent"),
]

file_list = []
for r in php_roots:
    file_list.extend(r.rglob("*.php"))

# Also search JS? skip
all_text_hits = Counter()
x_components = set()
for p in file_list:
    try:
        t = p.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        continue
    for pat in patterns[:-1]:
        for m in pat.findall(t):
            extra_refs.add(m.replace("/", "."))
            if pat.pattern.startswith("<x-"):
                x_components.add(m)

# Map x-foo.bar / x-foo-bar to components.foo.bar
comp_used = set()
for tag in x_components:
    tag = tag.split(" ")[0]
    dotted = tag.replace("-", ".")  # imperfect
    comp_used.add("components." + tag.replace("-", "."))
    # keep original dotted form: x-form.input -> components.form.input
    comp_used.add("components." + tag)

verified_unused = []
likely_framework = []
likely_component_unused = []
likely_pdf_or_dynamic = []
for b in INV["unused_blades"]:
    n = b["name"]
    aliases = {n, n.replace("_", "-")}
    if n.startswith("components."):
        rest = n[len("components.") :]
        aliases.update({rest, rest.replace(".", "-"), n})
        # x-form.input
        aliases.add(rest)
    hit = any(a in extra_refs or a in comp_used for a in aliases)
    if n.startswith("errors.") or n in {
        "auth.register",
        "auth.verify",
        "auth.passwords.confirm",
        "welcome",
        "home",
    }:
        likely_framework.append(b)
    elif n.startswith("components.") and not hit:
        likely_component_unused.append(b)
    elif any(x in n for x in (".pdf", "print", "public", "email", "mail")) and not hit:
        likely_pdf_or_dynamic.append(b)
    elif not hit:
        verified_unused.append(b)
    else:
        pass  # actually used

# Duplicate create/edit without shared form
blade_names = {b["name"] for b in INV["blades"]}
crud_dupes = []
parents = defaultdict(set)
for b in INV["blades"]:
    parts = b["name"].split(".")
    if len(parts) >= 2:
        parents[".".join(parts[:-1])].add(parts[-1])

create_edit_no_form = []
create_edit_with_form = []
index_only = []
for parent, leaves in sorted(parents.items()):
    has_c, has_e, has_f = "create" in leaves, "edit" in leaves, ("form" in leaves or "_form" in leaves or "partials.form" in {f"partials.{x}" for x in leaves})
    has_form = bool({"form", "_form"} & leaves) or any("form" in x for x in leaves)
    if has_c and has_e:
        if has_form:
            create_edit_with_form.append(parent)
        else:
            create_edit_no_form.append(parent)
    if leaves == {"index"} or leaves == {"index", "show"}:
        index_only.append(parent)

# Controllers with overlapping names
ctrl_by_basename = defaultdict(list)
for c in INV["controllers"]:
    base = re.sub(r"^(Api|Teacher|Parent)?", "", c["class"])
    ctrl_by_basename[c["class"].replace("Api", "").replace("Controller", "")].append(c)

overlap_controllers = []
# group by stripped name
stripped = defaultdict(list)
for c in INV["controllers"]:
    key = re.sub(r"Controller$", "", c["class"])
    key = re.sub(r"^Api", "", key)
    stripped[key].append(c)
for k, cs in stripped.items():
    if len(cs) >= 2:
        overlap_controllers.append((k, cs))

# Staff vs HR split
staff_hr = [c for c in INV["controllers"] if "Staff" in c["class"] or c["folder"] in ("Hr", "root") and "staff" in c["path"].lower()]

# Method name frequency
method_freq = Counter()
method_owners = defaultdict(list)
for c in INV["controllers"]:
    for m in c["methods"]:
        method_freq[m] += 1
        if method_freq[m] <= 6:
            method_owners[m].append(c["class"])

boilerplate = {"index", "create", "store", "show", "edit", "update", "destroy"}

# Tiny controllers (possible merge candidates)
tiny = [c for c in INV["controllers"] if c["method_count"] <= 2 and c["class"] not in {"Controller"}]

# Fat controllers
fat = sorted(INV["controllers"], key=lambda c: -c["method_count"])[:25]

# Docs by folder
docs_by = defaultdict(list)
for d in INV["docs"]:
    parts = d["path"].split("/")
    if parts[0] == "docs" and len(parts) > 2:
        folder = "/".join(parts[:2])
    else:
        folder = parts[0]
    docs_by[folder].append(d)

# --- write catalogs ---

def write(path: Path, text: str):
    path.write_text(text, encoding="utf-8")
    print("wrote", path, "chars", len(text))


# 11 blades
lines = [
    "# Blade view catalog (live inventory, 2026-09-22)",
    "",
    f"Total Blade files: **{INV['counts']['blades']}**.",
    "Purpose is inferred from path/filename plus module. `partial` means included by another view; `page` is typically returned by a controller.",
    "",
    "## Counts by folder",
    "",
    "| Folder | Views | Role |",
    "|---|---:|---|",
]
folder_roles = {
    "finance": "Fees, payments, invoices, receipts, accounting, M-Pesa, expenses",
    "academics": "Classes, CBC, exams, timetable, lesson plans, report cards",
    "communication": "SMS, email, WhatsApp, templates, announcements, notes",
    "students": "Registry, families, records, admissions, imports",
    "website": "Public CMS / school website admin",
    "dashboard": "Admin home KPIs and widgets",
    "hr": "Payroll, advances, statutory, staff analytics",
    "staff": "Staff CRUD, leave, documents, attendance, profile",
    "components": "Reusable Blade/UI kit (x- components)",
    "vendor": "Published package views (AdminLTE, mail, pagination)",
    "settings": "School settings, terms, placeholders, features",
    "pos": "School shop / uniforms / discounts",
    "inventory": "Items, requisitions, student requirements",
    "transport": "Routes, trips, assignments, import",
    "reports": "Weekly/class/subject/operations reports",
    "teacher": "Teacher portal screens",
    "partials": "Global shared snippets (SMS/email forms, terms)",
    "auth": "Login, passwords, 2FA/verify",
    "errors": "HTTP error pages (framework-wired)",
    "layouts": "App shells and role navs",
    "attendance": "Student attendance marking & reports",
    "families": "Family hub / linking",
    "operations": "Assets, visitors, concerns",
    "senior_teacher": "Senior teacher workspace",
    "activities": "Extra-curricular + parent requests",
    "student_assignments": "Student–class assignment UI",
    "swimming": "Swimming module",
    "dropoffpoints": "Drop-off point CRUD/import",
    "attendance_notifications": "Absence notification setup",
    "emails": "Mailable markdown/html",
    "events": "Calendar events",
    "family_update": "Parent self-service family update",
    "trips": "Trip CRUD (parallel to transport)",
    "documents": "Generated document UI",
    "driver": "Driver portal",
    "online_admissions": "Public/admin admissions",
    "student_categories": "Student category lookup",
    "vehicles": "Vehicle CRUD (parallel to transport)",
    "activity-logs": "User activity log",
    "admin": "Admin-only extras (senior teacher assignments)",
    "exports": "HTML/Excel-ish export layouts",
    "family": "Family-scoped screens (split from families/)",
    "legal": "Privacy / terms",
    "parent": "Parent diary portal",
    "pdf": "Generic PDF wrappers",
    "backup_restore": "Backup UI",
    "gallery": "Photo gallery",
    "home": "Legacy home",
    "parents": "Legacy/parent alias",
    "sms_logs": "SMS delivery log",
    "system-logs": "System log viewer",
    "users": "User admin",
    "welcome": "Laravel default welcome",
}
for k, v in INV["blade_top_counts"].items():
    lines.append(f"| `{k}` | {v} | {folder_roles.get(k, '')} |")

lines += ["", "## Every Blade file", ""]
by_top = defaultdict(list)
for b in INV["blades"]:
    by_top[b["top"]].append(b)

for top in sorted(by_top, key=lambda t: (-len(by_top[t]), t)):
    lines.append(f"### `{top}/` ({len(by_top[top])})")
    lines.append("")
    lines.append("| View name | Kind | Lines | Inferred purpose |")
    lines.append("|---|---|---:|---|")
    for b in sorted(by_top[top], key=lambda x: x["name"]):
        kind = "component" if b["is_component"] else "vendor" if b["is_vendor"] else "error" if b["is_error"] else "partial" if b["is_partial"] else "page"
        lines.append(
            f"| `{b['name']}` | {kind} | {b['lines']} | {purpose_from_name(b['name'])} |"
        )
    lines.append("")

write(OUT / "11-blade-catalog.md", "\n".join(lines))

# 12 controllers
clines = [
    "# Controller catalog (live inventory, 2026-09-22)",
    "",
    f"Total controllers: **{INV['counts']['controllers']}**. Public methods: **{INV['counts']['controller_methods']}**.",
    "The base `Controller` class is listed but is not a route target.",
    "",
    "## Counts by folder",
    "",
    "| Folder | Controllers | Role |",
    "|---|---:|---|",
]
cf_roles = {
    "Api": "Sanctum JSON API for mobile apps + website",
    "Finance": "Fees, payments, accounting, M-Pesa, expenses",
    "root": "Cross-cutting web (dashboard, comms, transport leftovers)",
    "Academics": "CBC, exams, timetable, lesson plans",
    "Website": "Public school website CMS",
    "Hr": "Payroll, leave, staff records, BioTime",
    "Students": "Registry, families, credentials, imports",
    "Inventory": "Stock and student requirements",
    "Pos": "School shop",
    "Reports": "Operational/academic report writers",
    "Teacher": "Teacher portal",
    "Swimming": "Swimming module",
    "Transport": "Trips, assignments, import",
    "Attendance": "Attendance + notifications",
    "Library": "Library cards/borrowing",
    "Operations": "Assets, visitors, concerns",
    "Activities": "Activity fees / parent requests",
    "Hostel": "Boarding (thin / possibly stale)",
    "Settings": "School settings",
    "WebAuthn": "Passkey login/register",
    "Admin": "Admin extras",
    "Auth": "Password change",
    "Driver": "Driver portal",
    "ParentPortal": "Parent diary web",
    "SeniorTeacher": "Senior teacher workspace",
    "Users": "Force password change",
}
for k, v in INV["controller_folder_counts"].items():
    clines.append(f"| `{k}` | {v} | {cf_roles.get(k, '')} |")

clines += ["", "## Every controller and its public methods", ""]
by_f = defaultdict(list)
for c in INV["controllers"]:
    by_f[c["folder"]].append(c)
for folder in sorted(by_f, key=lambda t: (-len(by_f[t]), t)):
    clines.append(f"### `{folder}` ({len(by_f[folder])})")
    clines.append("")
    for c in sorted(by_f[folder], key=lambda x: x["class"]):
        clines.append(f"#### `{c['class']}`")
        clines.append("")
        clines.append(f"- Path: `{c['path']}`")
        clines.append(f"- {purpose_controller(c['class'], c['folder'], c['methods'])}")
        clines.append(f"- Public methods ({c['method_count']}): {', '.join(f'`{m}`' for m in c['methods']) or '_none_'}")
        if c["views"]:
            clines.append(f"- Views returned: {', '.join(f'`{v}`' for v in c['views'][:20])}")
        clines.append("")

write(OUT / "12-controller-catalog.md", "\n".join(clines))

# 13 models
mlines = [
    "# Model catalog (live inventory, 2026-09-22)",
    "",
    f"Total Eloquent models: **{INV['counts']['models']}** (Concerns excluded from unused analysis).",
    "Table names are explicit `$table` when set, otherwise Laravel-style guess.",
    "`ref_files` is how many other PHP files mention the class name (not a proof of runtime use).",
    "",
    "## Counts by folder",
    "",
    "| Folder | Models |",
    "|---|---:|",
]
for k, v in INV["model_folder_counts"].items():
    mlines.append(f"| `{k}` | {v} |")

mlines += ["", "## Every model", ""]
by_f = defaultdict(list)
for m in INV["models"]:
    by_f[m["folder"]].append(m)
for folder in sorted(by_f, key=lambda t: (-len(by_f[t]), t)):
    mlines.append(f"### `{folder}` ({len(by_f[folder])})")
    mlines.append("")
    mlines.append("| Model | Table | Refs | Path | Purpose |")
    mlines.append("|---|---|---:|---|---|")
    for m in sorted(by_f[folder], key=lambda x: x["class"]):
        mlines.append(
            f"| `{m['class']}` | `{m['table']}` | {m['ref_files']} | `{m['path']}` | {purpose_model(m['class'], m['folder'], m['table'])} |"
        )
    mlines.append("")

write(OUT / "13-model-catalog.md", "\n".join(mlines))

# 14 docs
dlines = [
    "# Markdown document catalog (live inventory, 2026-09-22)",
    "",
    f"Total markdown files (excluding vendor/node_modules): **{INV['counts']['docs']}**.",
    "Many sprint/execution reports are historical and overlapping.",
    "",
]
for folder in sorted(docs_by):
    dlines.append(f"## `{folder}/` ({len(docs_by[folder])})")
    dlines.append("")
    dlines.append("| File | Title | Summary | Lines |")
    dlines.append("|---|---|---|---:|")
    for d in sorted(docs_by[folder], key=lambda x: x["path"]):
        dlines.append(
            f"| `{d['path']}` | {md_escape(d['title'])} | {md_escape(d['summary'][:180])} | {d['lines']} |"
        )
    dlines.append("")

write(OUT / "14-document-catalog.md", "\n".join(dlines))

# 15 redundancy
rlines = [
    "# Redundancy and dead-code findings (verified, 2026-09-22)",
    "",
    "Static analysis of the live tree. **Unused** means no `view()`, `@include`, `@extends`, `loadView`, or `<x-…>` string matched the view name. Dynamic/variable includes can hide real use — treat as candidates, not delete-without-review.",
    "",
    "## Headline numbers",
    "",
    f"- Blade files: {INV['counts']['blades']}",
    f"- Controllers / public methods: {INV['counts']['controllers']} / {INV['counts']['controller_methods']}",
    f"- Models: {INV['counts']['models']}",
    f"- Services / commands / jobs: {INV['counts']['services']} / {INV['counts']['commands']} / {INV['counts']['jobs']}",
    f"- Policies / FormRequests: {INV['counts']['policies']} / {INV['counts']['form_requests']}  (RBAC and validation are thin vs controller count)",
    f"- Markdown docs: {INV['counts']['docs']}",
    f"- Full CRUD view sets (index+create+edit+show): {INV['counts']['full_crud_view_sets']}",
    f"- Partial CRUD view sets: {INV['counts']['partial_crud_view_sets']}",
    f"- Create+edit **without** a shared form partial: {len(create_edit_no_form)}",
    f"- Create+edit **with** a shared form partial: {len(create_edit_with_form)}",
    "",
    "## 1. Likely-dead Blade pages (not framework, not unused design-system components)",
    "",
    "These did not match any static view reference. Highest-value cleanup candidates.",
    "",
    "| View | Lines | Why it looks dead |",
    "|---|---:|---|",
]
reasons = {
    "academics.assign_class_teacher": "Superseded by dedicated assignment screens / AssignTeachersController views",
    "academics.class": "Legacy singular class page; classrooms now live under academics.classrooms.*",
    "academics.class_timetable": "Superseded by academics.timetable.*",
    "academics.teacher_timetable": "Superseded by academics.timetable.teacher_*",
    "academics.promote_students": "Superseded by academics.promotions.*",
    "academics.sections": "No matching controller view() in Academics",
    "academics.curriculum_assistant.index": "Check CurriculumAssistantController — may have been renamed",
    "auth.register": "Laravel UI leftover; school accounts are provisioned, not self-registered",
    "auth.verify": "Laravel UI leftover unless email verification is enabled",
    "auth.passwords.confirm": "Laravel UI leftover",
    "welcome": "Default Laravel welcome",
    "home": "Default Laravel home vs DashboardController",
    "communication.templates.send_email": "Likely inlined into communication send flow",
    "communication.templates.send_sms": "Likely inlined into communication send flow",
    "student_assignments.bulk_assign": "Check StudentAssignmentController for renamed views",
    "student_assignments.create": "May be unused if assignment is modal/elsewhere",
    "student_assignments.edit": "May be unused if assignment is modal/elsewhere",
    "finance.credit_debit_adjustments.create": "Adjustments may be created from invoice screens only",
    "finance.credit_debit_adjustments.show": "Detail page unused if index is enough",
    "finance.invoices.adjust,blade": "TYPO filename (comma instead of dot) — unreachable",
}
for b in verified_unused:
    rlines.append(f"| `{b['name']}` | {b['lines']} | {reasons.get(b['name'], 'No static reference found')} |")

rlines += [
    "",
    f"Count: **{len(verified_unused)}** (after excluding error pages and unmatched x-components).",
    "",
    "## 2. Design-system Blade components with no `<x-…>` hits",
    "",
    "These sit in `resources/views/components` (Sprint UI kit). They may be unused because screens still use AdminLTE/Bootstrap markup, or they are referenced in a way the scanner missed.",
    "",
]
for b in likely_component_unused:
    rlines.append(f"- `{b['name']}` ({b['lines']} lines)")

rlines += [
    "",
    "## 3. PDF / print / public / email views with no static reference",
    "",
    "Often loaded dynamically (`Pdf::loadView($template)`). Confirm before deleting.",
    "",
]
for b in likely_pdf_or_dynamic:
    rlines.append(f"- `{b['name']}` ({b['lines']} lines)")

rlines += [
    "",
    "## 4. Framework-owned unused-looking views (do not delete casually)",
    "",
]
for b in likely_framework:
    rlines.append(f"- `{b['name']}`")

rlines += [
    "",
    "## 5. Create/edit duplication (no shared `_form` / `form` partial)",
    "",
    "Each pair likely copies the same fields twice. Highest-ROI Blade DRY.",
    "",
]
for p in create_edit_no_form:
    rlines.append(f"- `{p}.create` + `{p}.edit`")

rlines += [
    "",
    "## 6. Create/edit pairs that already share a form (good pattern)",
    "",
]
for p in create_edit_with_form:
    rlines.append(f"- `{p}`")

rlines += [
    "",
    "## 7. Parallel / overlapping modules (structural redundancy)",
    "",
    "These are not unused — they are **duplicate domains** that grew side by side.",
    "",
    "| Cluster | Locations | What it means |",
    "|---|---|---|",
    "| Staff vs HR | `staff/*` views + `StaffController` (root/Hr) vs `hr/*` | Staff CRUD/leave/profile lives beside payroll HR. Two IA trees for one people domain. |",
    "| Transport vs vehicles vs trips vs dropoffpoints | `transport/`, `vehicles/`, `trips/`, `dropoffpoints/` | Vehicle/trip/drop-off were built as separate CRUD apps instead of one transport bounded context. |",
    "| Family vs families vs family_update vs parent vs parents | five Blade trees | Family hub, parent diary, family update portal, and leftover `parents`/`family` folders overlap. |",
    "| Activity logs vs system-logs vs sms_logs | three one-page modules | Three log UIs instead of one observability area. |",
    "| Setting vs SystemSetting | `Setting` used; `SystemSetting` unused | Dead twin model. |",
    "| Hostel / mess / kitchen models | models with 0 refs | Boarding/mess was modelled then abandoned. |",
    "| HR performance/training models | Performance*, Training*, StaffSkill/Certification/Qualification | Staff 360 schema without web controllers. |",
    "| Extra-curricular double routes | `extra-curricular-activities` resource AND `activities` aliases | Same controller bound twice in `routes/web.php`. |",
    "| Website vs core school | 31 Website controllers + 48 Website models | A second product (public CMS) inside the ERP. |",
    "| Web Blade vs mobile API | 109 Api controllers vs 262 web | Same domains implemented twice (web+JSON) — expected, but method-level duplication is high. |",
    "",
    "## 8. Models with zero references outside themselves",
    "",
    "Genuinely orphaned Eloquent classes (no controller/service/route mention).",
    "",
]
for m in INV["unused_models"]:
    rlines.append(f"- `{m['class']}` — `{m['path']}` (table `{m.get('table')}`)")

rlines += [
    "",
    "## 9. Models used in only 1–2 files (thin / unfinished features)",
    "",
    "Not dead, but often a stub feature or a model only wired to its own controller.",
    "",
    "| Model | Files | Sample |",
    "|---|---:|---|",
]
for m in INV["lightly_used_models"]:
    rlines.append(f"| `{m['class']}` | {m['ref_files']} | {', '.join('`'+s+'`' for s in m['sample_refs'][:3])} |")

rlines += [
    "",
    "## 10. Controllers the scanner did not see as `FooController::class` in routes",
    "",
    "Several are **false positives** (`use` + alias, base class, Concerns). Verified notes:",
    "",
    "| Class | Verdict |",
    "|---|---|",
    "| `Controller` | Base class — ignore |",
    "| `ResolvesDocumentStorage` | Trait/Concern — ignore |",
    "| `ExtraCurricularActivityController` | **Routed** via alias `AcademicsExtraCurricularActivityController` |",
    "| `FeeStatementController` / `ReceiptController` | Imported in `web.php` — confirm route bodies |",
    "| `DiaryController` (ParentPortal) | **Routed** as `ParentDiaryController` |",
    "| `ExamGroupController` / `ExamPaperController` | Check nested academics exam routes |",
    "| `HomeController` | Possibly replaced by `DashboardController` |",
    "| `SmsLogController` | Has `sms_logs/index` view — confirm route |",
    "| `WebAuthnLoginController` / `WebAuthnRegisterController` | May be registered by Laragear package |",
    "| `ApiParentRequirementsController` (scanner label `requirements`) | False parse of a method/class fragment |",
    "",
    "## 11. Tiny controllers (merge candidates)",
    "",
    "Two or fewer public methods — often a one-off endpoint that could live on a parent controller.",
    "",
]
for c in sorted(tiny, key=lambda x: (x["folder"], x["class"])):
    rlines.append(f"- `{c['class']}` ({c['folder']}) — {', '.join(c['methods']) or 'no public methods'} — `{c['path']}`")

rlines += [
    "",
    "## 12. Largest controllers (god-class risk)",
    "",
    "| Controller | Methods | Lines | Folder |",
    "|---|---:|---:|---|",
]
for c in fat:
    rlines.append(f"| `{c['class']}` | {c['method_count']} | {c['lines']} | {c['folder']} |")

rlines += [
    "",
    "## 13. Duplicate controller names across web vs API vs role portals",
    "",
    "Same domain, multiple controllers. This is the main **method-level repetition** pattern.",
    "",
]
for k, cs in sorted(overlap_controllers, key=lambda kv: -len(kv[1])):
    if len(cs) < 2:
        continue
    rlines.append(f"- **{k}**: " + ", ".join(f"`{c['class']}` ({c['folder']}, {c['method_count']} methods)" for c in cs))

rlines += [
    "",
    "## 14. Policies vs controllers (authorization gap)",
    "",
    f"Only **{INV['counts']['policies']}** policies exist for **{INV['counts']['controllers']}** controllers. Authorization is mostly middleware/Spatie checks in controllers, not model policies. Form requests: **{INV['counts']['form_requests']}** — most controllers validate inline.",
    "",
    "## 15. Document redundancy",
    "",
    "169 markdown files. Heavy duplication across:",
    "",
    "- `docs/system-audit/*` (previous ERP audit, counts already stale vs this live pass)",
    "- `docs/execution/*` sprint reports",
    "- `docs/ui/*` + `docs/design-system-v3/*` overlapping UI specs",
    "- `docs/mobile-app-*` + `mobile-app/README.md` + `docs/USERS_APP_*` overlapping mobile audits",
    "- Root-level `docs/SYSTEM_DOCUMENTATION.md` (Jan 2026) vs this live catalog",
    "",
    "Keep one live inventory (this folder’s 11–15) and treat sprint reports as historical.",
    "",
]

write(OUT / "15-redundancy-findings.md", "\n".join(rlines))

# compact JSON for canvas
canvas = {
    "counts": INV["counts"],
    "blade_top_counts": INV["blade_top_counts"],
    "controller_folder_counts": INV["controller_folder_counts"],
    "model_folder_counts": INV["model_folder_counts"],
    "verified_unused_blades": [{"name": b["name"], "lines": b["lines"]} for b in verified_unused],
    "component_unused": [b["name"] for b in likely_component_unused],
    "pdf_unreferenced": [b["name"] for b in likely_pdf_or_dynamic],
    "unused_models": [m["class"] for m in INV["unused_models"]],
    "create_edit_no_form": create_edit_no_form,
    "create_edit_with_form": create_edit_with_form,
    "tiny_controllers": [{"class": c["class"], "folder": c["folder"], "methods": c["methods"]} for c in tiny],
    "fat_controllers": [{"class": c["class"], "methods": c["method_count"], "lines": c["lines"], "folder": c["folder"]} for c in fat],
    "overlap_controllers": [{"name": k, "items": [{"class": c["class"], "folder": c["folder"], "methods": c["method_count"]} for c in cs]} for k, cs in overlap_controllers if len(cs) >= 2],
    "docs_folders": {k: len(v) for k, v in docs_by.items()},
    "lightly_used_model_count": len(INV["lightly_used_models"]),
}
(OUT / "_canvas_data.json").write_text(json.dumps(canvas, indent=2), encoding="utf-8")
print("verified_unused", len(verified_unused))
print("component_unused", len(likely_component_unused))
print("pdf_unreferenced", len(likely_pdf_or_dynamic))
print("framework", len(likely_framework))
print("create_edit_no_form", len(create_edit_no_form))
print("tiny", len(tiny))
print("overlap groups", len([x for x in overlap_controllers if len(x[1])>=2]))
