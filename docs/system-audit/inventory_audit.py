#!/usr/bin/env python3
"""Live inventory + usage cross-reference for the school ERP audit."""
from __future__ import annotations

import json
import os
import re
from collections import Counter, defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
OUT = Path(__file__).resolve().parent / "_inventory.json"

VIEW_CALL_RE = re.compile(
    r"""(?:
        (?:return\s+)?view\(\s*['\"]([^'\"]+)['\"]
        | View::(?:make|first)\(\s*['\"]([^'\"]+)['\"]
        | ->view\(\s*['\"]([^'\"]+)['\"]
        | markdown\(\s*['\"]([^'\"]+)['\"]
        | Route::view\([^,]+,\s*['\"]([^'\"]+)['\"]
        | loadView\(\s*['\"]([^'\"]+)['\"]
        | PDF::loadView\(\s*['\"]([^'\"]+)['\"]
        | Pdf::loadView\(\s*['\"]([^'\"]+)['\"]
        | ->loadView\(\s*['\"]([^'\"]+)['\"]
    )""",
    re.VERBOSE,
)
INCLUDE_RE = re.compile(
    r"""(?:
        @include\(\s*['\"]([^'\"]+)['\"]
        | @includeIf\(\s*['\"]([^'\"]+)['\"]
        | @includeWhen\([^,]+,\s*['\"]([^'\"]+)['\"]
        | @includeFirst\(\s*\[([^\]]+)\]
        | @extends\(\s*['\"]([^'\"]+)['\"]
        | @component\(\s*['\"]([^'\"]+)['\"]
        | <x-([a-zA-Z0-9._:-]+)
        | @each\(\s*['\"]([^'\"]+)['\"]
        | @includeUnless\([^,]+,\s*['\"]([^'\"]+)['\"]
    )""",
    re.VERBOSE,
)
METHOD_RE = re.compile(
    r"^\s*public\s+function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(",
    re.MULTILINE,
)
TABLE_RE = re.compile(r"""protected\s+\$table\s*=\s*['\"]([^'\"]+)['\"]""")
CLASS_RE = re.compile(r"class\s+(\w+)")
ROUTE_CONTROLLER_RE = re.compile(
    r"""(?:
        ([A-Za-z0-9_\\]+Controller)::class
        | ['\"]([A-Za-z0-9_\\]+Controller)['\"]
    )""",
    re.VERBOSE,
)
ROUTE_ACTION_RE = re.compile(
    r"""(?:
        ::class\s*,\s*['\"](\w+)['\"]
        | Controller::class\]
        | \[\s*[A-Za-z0-9_\\]+::class\s*,\s*['\"](\w+)['\"]
        | ->name\(\s*['\"]([^'\"]+)['\"]
    )""",
    re.VERBOSE,
)
DOC_TITLE_RE = re.compile(r"^#\s+(.+)$", re.MULTILINE)


def rel(p: Path) -> str:
    return p.relative_to(ROOT).as_posix()


def blade_name(path: Path) -> str:
    rel_views = path.relative_to(ROOT / "resources" / "views").as_posix()
    return rel_views.replace("/", ".").replace(".blade.php", "")


def component_aliases(name: str) -> set[str]:
    """A blade under components/foo/bar maps to x-foo.bar / x-foo-bar etc."""
    aliases = {name}
    if name.startswith("components."):
        rest = name[len("components.") :]
        aliases.add(rest)
        aliases.add(rest.replace(".", "-"))
        aliases.add("x-" + rest)
        aliases.add("x-" + rest.replace(".", "-"))
        # anonymous components use x-folder.file
        aliases.add(rest.replace("_", "-"))
    return aliases


def walk(pattern_root: Path, glob: str):
    if not pattern_root.exists():
        return []
    return sorted(pattern_root.rglob(glob))


def read_text(p: Path) -> str:
    try:
        return p.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        return ""


def extract_includes(text: str) -> set[str]:
    found = set()
    for m in INCLUDE_RE.finditer(text):
        groups = [g for g in m.groups() if g]
        for g in groups:
            if "[" in g or "'" in g or '"' in g:
                for piece in re.findall(r"['\"]([^'\"]+)['\"]", g):
                    found.add(piece)
            else:
                found.add(g.strip())
    return found


def extract_view_calls(text: str) -> set[str]:
    found = set()
    for m in VIEW_CALL_RE.finditer(text):
        for g in m.groups():
            if g:
                found.add(g)
    return found


def controller_public_methods(text: str) -> list[str]:
    methods = METHOD_RE.findall(text)
    skip = {
        "middleware",
        "__construct",
        "__invoke",
        "__destruct",
        "__call",
        "__get",
        "__set",
        "__isset",
        "__unset",
        "__toString",
        "__invoke",
    }
    return [m for m in methods if m not in skip]


def model_table(path: Path, text: str) -> str:
    m = TABLE_RE.search(text)
    if m:
        return m.group(1)
    # Guess from class name
    cm = CLASS_RE.search(text)
    if not cm:
        return ""
    name = cm.group(1)
    # naive pluralize
    if name.endswith("y") and not name.endswith(("ay", "ey", "oy", "uy")):
        return re.sub(r"(?<!^)(?=[A-Z])", "_", name[:-1]).lower() + "ies"
    if name.endswith("s"):
        return re.sub(r"(?<!^)(?=[A-Z])", "_", name).lower() + "es"
    return re.sub(r"(?<!^)(?=[A-Z])", "_", name).lower() + "s"


def main():
    blades = list(walk(ROOT / "resources" / "views", "*.blade.php"))
    controllers = list(walk(ROOT / "app" / "Http" / "Controllers", "*.php"))
    models = [
        p
        for p in walk(ROOT / "app" / "Models", "*.php")
        if "Concerns" not in p.as_posix()
    ]
    services = list(walk(ROOT / "app" / "Services", "*.php"))
    jobs = list(walk(ROOT / "app" / "Jobs", "*.php"))
    commands = list(walk(ROOT / "app" / "Console" / "Commands", "*.php"))
    mail = list(walk(ROOT / "app" / "Mail", "*.php"))
    notifications = list(walk(ROOT / "app" / "Notifications", "*.php"))
    observers = list(walk(ROOT / "app" / "Observers", "*.php"))
    policies = list(walk(ROOT / "app" / "Policies", "*.php"))
    requests = list(walk(ROOT / "app" / "Http" / "Requests", "*.php"))
    middleware = list(walk(ROOT / "app" / "Http" / "Middleware", "*.php"))
    exports = list(walk(ROOT / "app" / "Exports", "*.php"))
    docs = [
        p
        for p in walk(ROOT, "*.md")
        if "node_modules" not in p.as_posix()
        and "vendor" not in p.as_posix()
        and ".cursor" not in p.as_posix()
    ]
    routes = list(walk(ROOT / "routes", "*.php"))
    php_scan_roots = [
        ROOT / "app",
        ROOT / "routes",
        ROOT / "database",
        ROOT / "resources" / "views",
        ROOT / "tests",
    ]

    blade_meta = []
    blade_by_name = {}
    for p in blades:
        name = blade_name(p)
        top = name.split(".")[0]
        size = p.stat().st_size
        text = read_text(p)
        includes = extract_includes(text)
        blade_meta.append(
            {
                "name": name,
                "path": rel(p),
                "top": top,
                "bytes": size,
                "lines": text.count("\n") + 1,
                "includes": sorted(includes),
                "is_partial": "/partials/" in p.as_posix()
                or name.split(".")[-1].startswith("_")
                or ".partials." in name
                or p.name.startswith("_"),
                "is_component": name.startswith("components."),
                "is_vendor": name.startswith("vendor."),
                "is_error": name.startswith("errors."),
                "is_mail": name.startswith("vendor.mail")
                or name.startswith("emails.")
                or name.startswith("mail."),
            }
        )
        blade_by_name[name] = p

    # Collect all references from PHP + blades
    referenced_views: set[str] = set()
    view_ref_from: dict[str, set[str]] = defaultdict(set)

    php_files = []
    for r in php_scan_roots:
        php_files.extend(walk(r, "*.php"))

    for p in php_files:
        text = read_text(p)
        src = rel(p)
        if p.suffix == ".php" and "blade.php" in p.name:
            incs = extract_includes(text)
            calls = extract_view_calls(text)
            refs = incs | calls
        else:
            refs = extract_view_calls(text) | extract_includes(text)
        for v in refs:
            # normalize component tags
            nv = v
            if nv.startswith("x-"):
                nv = nv[2:].replace(":", ".")
            nv = nv.replace("/", ".")
            referenced_views.add(nv)
            view_ref_from[nv].add(src)
            # also add components. prefix variants
            if not nv.startswith("components."):
                referenced_views.add("components." + nv)
                view_ref_from["components." + nv].add(src)

    # Also scan JS for blade? skip.

    unused_blades = []
    used_blades = []
    for b in blade_meta:
        name = b["name"]
        aliases = {name, name.replace("_", "-")}
        if name.startswith("components."):
            rest = name[len("components.") :]
            aliases.update({rest, rest.replace(".", "-"), "x-" + rest})
        # layouts.adminlte used via @extends
        hit = False
        sources = set()
        for a in aliases:
            if a in referenced_views:
                hit = True
                sources |= view_ref_from.get(a, set())
            # prefix match for dynamic includes is NOT counted as used
        # layouts referenced via @extends('layouts.app')
        # vendor/adminlte
        if name.startswith("vendor."):
            hit = True
            sources.add("(vendor/package)")
        if hit:
            used_blades.append({**b, "refs": sorted(sources)[:20], "ref_count": len(sources)})
        else:
            unused_blades.append(b)

    # Controllers
    controller_meta = []
    all_controller_classes = {}
    for p in controllers:
        text = read_text(p)
        cm = CLASS_RE.search(text)
        cls = cm.group(1) if cm else p.stem
        methods = controller_public_methods(text)
        views_returned = sorted(extract_view_calls(text))
        ns_match = re.search(r"namespace\s+([^;]+);", text)
        ns = ns_match.group(1) if ns_match else ""
        fqn = f"{ns}\\{cls}" if ns else cls
        all_controller_classes[cls] = fqn
        controller_meta.append(
            {
                "class": cls,
                "fqn": fqn,
                "path": rel(p),
                "folder": "/".join(rel(p).split("/")[3:-1]) or "root",
                "methods": methods,
                "method_count": len(methods),
                "views": views_returned,
                "bytes": p.stat().st_size,
                "lines": text.count("\n") + 1,
            }
        )

    # Route references
    route_text = "\n".join(read_text(p) for p in routes)
    routed_controllers = set()
    for m in ROUTE_CONTROLLER_RE.finditer(route_text):
        g = m.group(1) or m.group(2)
        routed_controllers.add(g.split("\\")[-1])

    # Also scan whole app for Controller::class usage (route files + RouteServiceProvider etc)
    extra_php = list(walk(ROOT / "app" / "Providers", "*.php"))
    for p in extra_php + php_files:
        t = read_text(p)
        for m in ROUTE_CONTROLLER_RE.finditer(t):
            g = m.group(1) or m.group(2)
            routed_controllers.add(g.split("\\")[-1])

    unrouted_controllers = [
        c for c in controller_meta if c["class"] not in routed_controllers
    ]

    # Method usage: count references of ->method or 'method' near controller
    # Simpler: grep each public method name across routes + other files
    method_use = []
    # Build a blob of routes for method name search
    one_shot_methods = []
    for c in controller_meta:
        text = read_text(ROOT / c["path"])
        for mth in c["methods"]:
            # count appearances of 'methodName' in routes
            route_hits = len(re.findall(rf"['\"]{re.escape(mth)}['\"]", route_text))
            # count $this->method in same controller
            internal = len(re.findall(rf"\$this->{re.escape(mth)}\s*\(", text))
            # count other PHP references Controller@method or ::class, 'method'
            # Approximate: if route_hits == 0 and not __invoke, likely unused or invoked dynamically
            if route_hits == 0 and mth not in {"index", "create", "store", "show", "edit", "update", "destroy"}:
                # still might be resource-mapped; resource maps those 7
                one_shot_methods.append(
                    {
                        "controller": c["class"],
                        "method": mth,
                        "route_string_hits": route_hits,
                        "path": c["path"],
                    }
                )
        method_use.append(c)

    # Models
    model_meta = []
    app_blob_files = list(walk(ROOT / "app", "*.php")) + list(walk(ROOT / "routes", "*.php")) + list(
        walk(ROOT / "database", "*.php")
    )
    file_texts: list[tuple[str, str]] = []
    for p in app_blob_files:
        file_texts.append((rel(p), read_text(p)))

    model_names = []
    for p in models:
        text = read_text(p)
        cm = CLASS_RE.search(text)
        cls = cm.group(1) if cm else p.stem
        if cls.endswith("Test"):
            continue
        if "namespace" not in text[:400] and "class " not in text[:500]:
            continue
        ns_match = re.search(r"namespace\s+([^;]+);", text)
        ns = ns_match.group(1) if ns_match else "App\\Models"
        table = model_table(p, text)
        has_table = bool(TABLE_RE.search(text))
        model_names.append(cls)
        model_meta.append(
            {
                "class": cls,
                "fqn": f"{ns}\\{cls}",
                "path": rel(p),
                "folder": "/".join(rel(p).split("/")[2:-1]) or "root",
                "table": table,
                "explicit_table": has_table,
                "bytes": p.stat().st_size,
                "lines": text.count("\n") + 1,
            }
        )

    unused_models = []
    lightly_used_models = []
    for m in model_meta:
        cls = m["class"]
        needle = cls
        hits = 0
        hit_files = []
        self_path = m["path"]
        for fpath, t in file_texts:
            if fpath == self_path:
                continue
            if needle in t:
                hits += 1
                if len(hit_files) < 8:
                    hit_files.append(fpath)
        m["ref_files"] = hits
        m["sample_refs"] = hit_files
        if hits == 0:
            unused_models.append(m)
        elif hits <= 2:
            lightly_used_models.append(m)

    # Duplicate blade filenames
    fname_map = defaultdict(list)
    for b in blade_meta:
        fname_map[Path(b["path"]).name].append(b["path"])
    duplicate_filenames = {
        k: v for k, v in fname_map.items() if len(v) >= 3 and k in
        {"index.blade.php", "create.blade.php", "edit.blade.php", "show.blade.php", "form.blade.php"}
        or (len(v) >= 2 and k not in {"index.blade.php", "create.blade.php", "edit.blade.php", "show.blade.php"})
    }
    # Too noisy; keep only non-CRUD duplicates
    non_crud_dupes = {
        k: v
        for k, v in fname_map.items()
        if len(v) >= 2
        and k
        not in {
            "index.blade.php",
            "create.blade.php",
            "edit.blade.php",
            "show.blade.php",
            "form.blade.php",
        }
    }

    # CRUD resource blade sets
    resource_sets = defaultdict(lambda: {"index": False, "create": False, "edit": False, "show": False})
    for b in blade_meta:
        parts = b["name"].split(".")
        if len(parts) >= 2:
            last = parts[-1]
            parent = ".".join(parts[:-1])
            if last in resource_sets[parent]:
                resource_sets[parent][last] = True
    full_crud = [k for k, v in resource_sets.items() if all(v.values())]
    partial_crud = [
        {"resource": k, **v}
        for k, v in resource_sets.items()
        if sum(v.values()) >= 2 and not all(v.values())
    ]

    # Docs
    doc_meta = []
    for p in docs:
        text = read_text(p)
        titles = DOC_TITLE_RE.findall(text)
        first_para = ""
        for line in text.splitlines():
            s = line.strip()
            if s and not s.startswith("#") and not s.startswith(">") and not s.startswith("---"):
                first_para = s[:240]
                break
        doc_meta.append(
            {
                "path": rel(p),
                "title": titles[0] if titles else p.stem,
                "headings": titles[:8],
                "summary": first_para,
                "bytes": p.stat().st_size,
                "lines": text.count("\n") + 1,
            }
        )

    # Blade top-level counts
    top_counts = Counter(b["top"] for b in blade_meta)
    controller_folder_counts = Counter(c["folder"] for c in controller_meta)
    model_folder_counts = Counter(m["folder"] for m in model_meta)

    # Repetitive create/edit pairs sharing a form partial
    form_partials = [b["name"] for b in blade_meta if b["name"].endswith(".form") or b["name"].endswith(".partials.form")]

    # One-use blades: used but only 1 ref and not a layout/component/vendor
    one_use_blades = [
        b
        for b in used_blades
        if b.get("ref_count", 0) == 1
        and not b["is_vendor"]
        and not b["is_component"]
        and not b["name"].startswith("layouts.")
        and not b["is_error"]
    ]

    inventory = {
        "counts": {
            "blades": len(blades),
            "controllers": len(controllers),
            "controller_methods": sum(c["method_count"] for c in controller_meta),
            "models": len(model_meta),
            "services": len(services),
            "jobs": len(jobs),
            "commands": len(commands),
            "mail": len(mail),
            "notifications": len(notifications),
            "observers": len(observers),
            "policies": len(policies),
            "form_requests": len(requests),
            "middleware": len(middleware),
            "exports": len(exports),
            "docs": len(docs),
            "unused_blades": len(unused_blades),
            "unrouted_controllers": len(unrouted_controllers),
            "unused_models": len(unused_models),
            "lightly_used_models": len(lightly_used_models),
            "one_use_blades": len(one_use_blades),
            "full_crud_view_sets": len(full_crud),
            "partial_crud_view_sets": len(partial_crud),
        },
        "blade_top_counts": dict(top_counts.most_common()),
        "controller_folder_counts": dict(controller_folder_counts.most_common()),
        "model_folder_counts": dict(model_folder_counts.most_common()),
        "blades": blade_meta,
        "unused_blades": unused_blades,
        "one_use_blades": [
            {"name": b["name"], "path": b["path"], "refs": b.get("refs", []), "lines": b["lines"]}
            for b in sorted(one_use_blades, key=lambda x: x["name"])
        ],
        "controllers": [
            {
                "class": c["class"],
                "folder": c["folder"],
                "path": c["path"],
                "methods": c["methods"],
                "method_count": c["method_count"],
                "views": c["views"],
                "lines": c["lines"],
            }
            for c in controller_meta
        ],
        "unrouted_controllers": [
            {"class": c["class"], "path": c["path"], "methods": c["methods"]}
            for c in unrouted_controllers
        ],
        "possible_unmapped_methods": one_shot_methods,
        "models": [
            {
                "class": m["class"],
                "folder": m["folder"],
                "path": m["path"],
                "table": m["table"],
                "explicit_table": m["explicit_table"],
                "ref_files": m["ref_files"],
                "sample_refs": m["sample_refs"],
                "lines": m["lines"],
            }
            for m in model_meta
        ],
        "unused_models": unused_models,
        "lightly_used_models": lightly_used_models,
        "non_crud_duplicate_filenames": non_crud_dupes,
        "full_crud_view_sets": full_crud,
        "partial_crud_view_sets": partial_crud,
        "form_partials": form_partials,
        "docs": doc_meta,
        "services": [rel(p) for p in services],
        "jobs": [rel(p) for p in jobs],
        "commands": [rel(p) for p in commands],
    }

    OUT.write_text(json.dumps(inventory, indent=2), encoding="utf-8")
    print("Wrote", OUT)
    print(json.dumps(inventory["counts"], indent=2))
    print("\nTop blade folders:")
    for k, v in top_counts.most_common(25):
        print(f"  {k:24} {v}")
    print("\nUnused blades (first 80):")
    for b in unused_blades[:80]:
        print(" ", b["name"])
    print("\nUnrouted controllers:")
    for c in unrouted_controllers:
        print(" ", c["class"], c["path"])
    print("\nUnused models:")
    for m in unused_models:
        print(" ", m["class"], m["path"])
    print("\nLightly used models:")
    for m in lightly_used_models:
        print(" ", m["class"], m["ref_files"], m["sample_refs"][:3])


if __name__ == "__main__":
    main()
