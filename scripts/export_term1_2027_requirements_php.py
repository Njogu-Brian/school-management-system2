"""Export CLASSES from create_buying_pricelist_pdf.py into database/data/term1_2027_requirements.php."""
import ast
import math
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SRC = Path(__file__).resolve().parent / "create_buying_pricelist_pdf.py"
OUT = ROOT / "database" / "data" / "term1_2027_requirements.php"

src = SRC.read_text(encoding="utf-8")
# Truncate before branded_header so we only parse data + CLASSES
cut = src.find("\ndef branded_header")
if cut > 0:
    src = src[:cut]

tree = ast.parse(src)
wanted = {"r5", "U", "_RAW", "B", "CLASSES", "STORY_BRANDS"}
ns: dict = {"math": math}


class SafeLoader(ast.NodeVisitor):
    def visit_FunctionDef(self, node: ast.FunctionDef):
        if node.name == "r5":
            exec(compile(ast.Module(body=[node], type_ignores=[]), str(SRC), "exec"), ns)
        self.generic_visit(node)

    def visit_Assign(self, node: ast.Assign):
        for t in node.targets:
            if isinstance(t, ast.Name) and t.id in wanted:
                exec(compile(ast.Module(body=[node], type_ignores=[]), str(SRC), "exec"), ns)
        self.generic_visit(node)


SafeLoader().visit(tree)
CLASSES = ns["CLASSES"]

VERIFY_NAMES = {
    "Students Dictionary",
    "Access Atlas",
    "Mentor Atlas",
    "Kamusi ya Karne ya 21",
    "Good News Bible",
    "Geometrical set",
    "Scientific Calculator",
    "Bridges Without Rivers and Other Stories (Access)",
    "Melodies of Africa and Other Poems (Access)",
    "Daughter of Nature and Other Poems (Access)",
    "Mshale wa Matumaini",
    "Mji wa Matarajio (One Planet)",
    "Uteuzi wa Chale Lvl 7 (One Planet)",
    "Wema Hauozi",
}


def php_str(s):
    if s is None:
        return "null"
    return "'" + str(s).replace("\\", "\\\\").replace("'", "\\'") + "'"


def map_class(title: str) -> str:
    return title.split("–")[0].strip()


def infer_category(name: str) -> str:
    name_l = name.lower()
    if any(x in name_l for x in ["tissue", "wipe"]):
        return "toiletries"
    book_hints = [
        "book", "workbook", "diary", "shajara", "dictionary", "kamusi", "bible",
        "atlas", "queenex", "oxford", "ladybird", "storymoja", "pioneer",
        "bridges", "melodies", "daughter", "mshale", "mji", "uteuzi", "wema",
        "targeter", "skyline", "sound and read", "story books",
    ]
    if any(x in name_l for x in book_hints):
        return "books"
    return "stationery"


def infer_unit(name: str, category: str) -> str:
    name_l = name.lower()
    if "tissue" in name_l:
        return "rolls"
    if "ream" in name_l:
        return "ream"
    if category == "books":
        return "book"
    if "pencil" in name_l and "colour" not in name_l and "pouch" not in name_l:
        return "packet"
    return "piece"


def is_verify(name: str, leave: bool) -> bool:
    if leave:
        return False
    if name in VERIFY_NAMES:
        return True
    name_l = name.lower()
    return any(
        x in name_l
        for x in ["dictionary", "atlas", "kamusi", "bible", "geometrical", "calculator"]
    )


lines = [
    "<?php",
    "",
    "if (! function_exists('term1_2027_requirement_item')) {",
    "    function term1_2027_requirement_item(",
    "        string $name,",
    "        float $quantity,",
    "        string $unit,",
    "        ?string $brand,",
    "        string $category,",
    "        bool $leaveWithTeacher,",
    "        float $unitPrice,",
    "        bool $verificationOnly = false,",
    "        ?string $notes = null,",
    "        ?string $imageSourceUrl = null",
    "    ): array {",
    "        return [",
    "            'name' => $name,",
    "            'quantity' => $quantity,",
    "            'unit' => $unit,",
    "            'brand' => $brand,",
    "            'category' => $category,",
    "            'leave_with_teacher' => $leaveWithTeacher,",
    "            'unit_price' => $unitPrice,",
    "            'is_verification_only' => $verificationOnly,",
    "            'notes' => $notes,",
    "            'image_source_url' => $imageSourceUrl,",
    "        ];",
    "    }",
    "}",
    "",
    "/** Term 1 2027 learning requirements (new + continuing). Generated from buying pricelist. */",
    "return [",
    "",
]

for title, sections in CLASSES:
    label = map_class(title)
    lines.append(f"    {php_str(label)} => [")
    for sec_name, items in sections:
        leave = sec_name.startswith("A.")
        for item, qty, unit, brand in items:
            price = float(unit) if unit is not None else 0.0
            cat = infer_category(item)
            u = infer_unit(item, cat)
            verify = is_verify(item, leave)
            notes = "Buy only if missing or unusable." if verify else None
            brand_s = brand if brand else None
            lines.append(
                "        term1_2027_requirement_item("
                f"{php_str(item)}, {qty}, {php_str(u)}, {php_str(brand_s)}, "
                f"{php_str(cat)}, {'true' if leave else 'false'}, {price:.0f}, "
                f"{'true' if verify else 'false'}, {php_str(notes)}),"
            )
    lines.append("    ],")
    lines.append("")

lines.append("];")
lines.append("")

OUT.parent.mkdir(parents=True, exist_ok=True)
OUT.write_text("\n".join(lines), encoding="utf-8")
print(f"Wrote {OUT}")
for title, sections in CLASSES:
    n = sum(len(i) for _, i in sections)
    print(f"  {map_class(title)}: {n} items")
