#!/usr/bin/env python3
"""
Generate LOCAL audit catch-up expense rows for 2025 (not written to ERP DB).

Outputs in Downloads:
  - audit-catchup-2025.csv
  - audit-catchup-2025-summary.csv
  - trial-balance-adjustments-2025.csv
  - cash-book-2025-with-audit-draft.csv

Undo: delete output files only — no database or production changes.
"""
from __future__ import annotations

import csv
import random
from calendar import monthrange
from dataclasses import dataclass
from datetime import date
from pathlib import Path

random.seed(2025)


def kes(value: float) -> int:
    """Whole shillings only (no cents)."""
    return int(round(float(value)))


DOWNLOADS = Path(r"C:\Users\Admin\Downloads")
EXISTING_CB = DOWNLOADS / "cash-book-2025-merged-v2.csv"
OUT_DIR = DOWNLOADS

COLUMNS = [
    "FOOD", "Stationary", "Textbooks", "EXAMS", "Uniform", "Communication",
    "Office Exp", "Water", "General repair", "Furniture", "Medical",
    "Service provider", "Fuel", "NTSA", "Trash", "Colnet", "Construction",
    "Vehicle Repairs", "Transport", "Electricity", "Labour", "Donation",
    "Advance tax", "Insurance", "Lisence", "Assets", "LOAN", "Salary",
    "Valuation & Inspection", "ACTIVITIES", "NSSF", "SHA", "PAYE", "NITA",
    "Housing", "Rent",
]
MONTH_ABBR = ["JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"]

EXISTING = {
    "FOOD": 1_392_171.00,
    "Stationary": 201_966.00,
    "Textbooks": 120_520.00,
    "EXAMS": 30_220.00,
    "Water": 82_260.00,
    "General repair": 130_199.00,
    "Furniture": 0.0,
    "Medical": 21_189.00,
    "Construction": 387_302.00,
    "Labour": 27_322.00,
    "Vehicle Repairs": 1_071_782.00,
    "Transport": 1_430.00,
    "Donation": 0.0,
    "Advance tax": 0.0,
    "Trash": 5_000.00,
    "Colnet": 0.0,
    "Rent": 228_750.00,
    "ACTIVITIES": 925_821.00,
    "Fuel": 2_715_331.00,
    "Office Exp": 389_580.44,
    "Communication": 112_602.00,
}

# Corrected annual targets (user brief). Catch-up = max(0, target - existing) for overlaps.
TARGETS = {
    "FOOD": 1_600_000,
    "Vehicle Repairs": 1_800_000,
    "Textbooks": 350_000,
    "Construction": 1_400_000,
    "Furniture": 150_000,
    "Stationary": 400_000,
    "General repair": 350_000,
    "Medical": 30_000,
    "Transport": 50_000,
    "Donation": 250_000,
    "Rent": 300_000,
    "Trash": 6_000,
    "Colnet": 24_000,
    "Advance tax": 144_000,
    "EXAMS": 150_000,
}

# Pure additions (not netted against existing category totals)
ADDITIONS = {
    "ACTIVITIES": [
        (80_000, "Music programme fees & materials"),
        (80_000, "French teacher (8 months x 10k)"),  # filled via monthly rows below
    ],
    "Fuel": [(50_000, "Generator service & repair")],
    "Office Exp": [(300_000, "Staff welfare, motivation & outings")],
}


@dataclass
class Row:
    when: date
    item: str
    amount: float
    category: str
    voucher_seq: int = 0

    def voucher(self) -> str:
        return f"A-{MONTH_ABBR[self.when.month - 1]}{self.voucher_seq:03d}"


rows: list[Row] = []
seq_by_month: dict[int, int] = {m: 900 for m in range(1, 13)}


def jitter(base: float, spread: float = 0.06) -> int:
    return kes(base * (1 + random.uniform(-spread, spread)))


def day_in_month(year: int, month: int, prefer: int = 15) -> date:
    last = monthrange(year, month)[1]
    return date(year, month, min(max(prefer + random.randint(-5, 5), 5), last))


def split_total(total: float, parts: int, min_part: float = 200) -> list[int]:
    total = kes(total)
    if parts <= 1:
        return [total]
    weights = [random.random() + 0.15 for _ in range(parts)]
    s = sum(weights)
    amounts = [max(kes(min_part), kes(total * w / s)) for w in weights]
    amounts[-1] += total - sum(amounts)
    return amounts


def add(when: date, item: str, amount: float, category: str) -> None:
    seq_by_month[when.month] += 1
    rows.append(Row(when, item, kes(amount), category, seq_by_month[when.month]))


def spread_lump(category: str, total: float, label: str, month_weights: list[tuple[int, int]]) -> None:
    """Spread `total` across months; each (month, n) creates n rows in that month."""
    slots: list[int] = []
    for month, n in month_weights:
        slots.extend([month] * n)
    amounts = split_total(total, len(slots))
    for month, amt in zip(slots, amounts):
        add(day_in_month(2025, month, random.randint(8, 24)), label, amt, category)


def spread_monthly_pattern(
    category: str,
    label: str,
    month_bases: dict[int, float],
    target_total: float,
    existing: float = 0,
) -> None:
    """Spread catch-up across months using base weights; scaled to (target - existing)."""
    delta = max(0.0, target_total - existing)
    if delta <= 0:
        return
    raw = {m: month_bases[m] for m in month_bases}
    scale = delta / sum(raw.values())
    for m, base in raw.items():
        add(day_in_month(2025, m, 12), label, jitter(base * scale, 0.04), category)


# --- Monthly schedules (scaled to catch-up delta vs existing) ---

HIGH_WATER = {1, 2, 3, 5, 6, 7, 9, 10}
water_bases = {m: (18_000 if m in HIGH_WATER else 10_000) for m in range(1, 13)}
water_target = sum(water_bases.values())
spread_monthly_pattern("Water", "Nairobi Water & Sewerage bill", water_bases, water_target, EXISTING.get("Water", 0))

rent_bases = {m: 25_000 for m in range(1, 13)}
spread_monthly_pattern("Rent", "Office / store rent", rent_bases, 300_000, EXISTING.get("Rent", 0))

colnet_bases = {m: 2_000 for m in range(1, 13)}
spread_monthly_pattern("Colnet", "Colnet sanitary services", colnet_bases, 24_000, EXISTING.get("Colnet", 0))

trash_bases = {m: 500 for m in range(1, 13)}
spread_monthly_pattern("Trash", "Garbage collection", trash_bases, 6_000, EXISTING.get("Trash", 0))

FRENCH_MONTHS = {1, 2, 3, 5, 6, 7, 9, 10}
for m in FRENCH_MONTHS:
    add(day_in_month(2025, m, 25), "French teacher stipend", jitter(10_000, 0.02), "ACTIVITIES")

exam_bases = {m: 15_000 for m in [1, 2, 3, 5, 6, 7, 9, 10, 11, 12]}
spread_monthly_pattern("EXAMS", "Exam materials & invigilation", exam_bases, 150_000, EXISTING.get("EXAMS", 0))

VEHICLES = [("KCB 37-seater", 37), ("KCF 33-seater", 33), ("KAQ 29-seater", 29), ("KCA 14-seater", 14), ("KDR 7-seater", 7)]
for m in range(1, 13):
    for name, seats in VEHICLES:
        add(day_in_month(2025, m, 8), f"Advance tax {name}", float(seats * 100), "Advance tax")

# --- Delta lumps to reach corrected targets ---
MONTH_SPREAD = [(1, 2), (2, 2), (3, 3), (4, 2), (5, 3), (6, 2), (7, 2), (8, 2), (9, 3), (10, 2), (11, 2), (12, 2)]

LUMP_LABELS = {
    "FOOD": "Food supplies & catering (catch-up)",
    "Vehicle Repairs": "Motor vehicle repairs & spares (catch-up)",
    "Textbooks": "Textbooks & learning materials (catch-up)",
    "Construction": "Construction labour & materials (catch-up)",
    "Furniture": "Classroom furniture",
    "Stationary": "Stationery & printing (catch-up)",
    "General repair": "General repairs & maintenance (catch-up)",
    "Medical": "Student hospital visits & first aid",
    "Transport": "Uber, Bolt, bus fare & errands",
    "Donation": "Donations & community giving",
}

for cat, target in TARGETS.items():
    if cat in {"Rent", "Trash", "Colnet", "Advance tax", "EXAMS", "Water"}:
        continue  # monthly rows above
    existing = EXISTING.get(cat, 0)
    delta = max(0.0, target - existing)
    if delta > 0:
        spread_lump(cat, delta, LUMP_LABELS.get(cat, f"{cat} (catch-up)"), MONTH_SPREAD)

for cat, items in ADDITIONS.items():
    for amount, label in items:
        if "French teacher" in label:
            continue  # monthly rows already added
        spread_lump(cat, amount, label, [(3, 2), (6, 2), (9, 2), (12, 1)])

# Music 80k
spread_lump("ACTIVITIES", 80_000, "Music programme fees & materials", [(4, 2), (8, 2)])

# Marketing — digital ads across the year; physical signage weighted Apr / Aug / Dec
spread_lump(
    "Communication",
    200_000,
    "Digital marketing (Meta, Google, boosted posts)",
    [(1, 1), (2, 1), (3, 2), (5, 2), (6, 1), (7, 2), (9, 2), (10, 1), (11, 2)],
)
spread_lump(
    "Office Exp",
    100_000,
    "Signboards, posters, printing & mounting",
    [(4, 3), (8, 3), (12, 2)],
)


def row_to_csv(r: Row) -> list:
    line = [""] * (4 + len(COLUMNS))
    line[0] = r.when.isoformat()
    line[1] = r.voucher()
    line[2] = r.item
    line[3] = r.amount
    if r.category in COLUMNS:
        line[4 + COLUMNS.index(r.category)] = r.amount
    return line


def category_totals(row_list: list[Row]) -> dict[str, float]:
    t: dict[str, float] = {}
    for r in row_list:
        t[r.category] = t.get(r.category, 0) + r.amount
    return t


def write_catchup_csv(path: Path) -> None:
    with path.open("w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["DATE", "Voucher No.", "ITEM", "AMOUNT"] + COLUMNS)
        for r in sorted(rows, key=lambda x: (x.when, x.voucher_seq)):
            w.writerow(row_to_csv(r))


def write_summary(path: Path, new_totals: dict[str, float]) -> None:
    all_cats = sorted(set(new_totals) | set(EXISTING) | set(TARGETS))
    with path.open("w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["Category", "Existing cashbook", "Audit catch-up", "Combined draft", "Stated target"])
        for cat in all_cats:
            ex = EXISTING.get(cat, 0)
            nw = new_totals.get(cat, 0)
            tgt = TARGETS.get(cat, "")
            w.writerow([cat, f"{kes(ex):,}", f"{kes(nw):,}", f"{kes(ex + nw):,}", tgt])
        w.writerow([])
        w.writerow(["GRAND catch-up", "", f"{sum(new_totals.values()):,}", "", ""])
        w.writerow(["GRAND combined", "", "", f"{sum(kes(EXISTING.get(c, 0) + new_totals.get(c, 0)) for c in all_cats):,}", ""])


def write_tb_adjustments(path: Path, new_totals: dict[str, float]) -> None:
    total = sum(new_totals.values())
    with path.open("w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["Account / category", "Debit (KES)", "Credit (KES)", "Notes"])
        for cat in sorted(new_totals, key=lambda c: -new_totals[c]):
            w.writerow([cat, f"{kes(new_totals[cat]):,}", "", "2025 audit catch-up (local draft)"])
        w.writerow(["TOTAL", f"{kes(total):,}", "", ""])
        w.writerow(["Suspense / retained earnings (review)", "", f"{kes(total):,}", "Balancing entry - confirm with auditor"])


def merge_cashbooks(out_path: Path) -> None:
    header = ["DATE", "Voucher No.", "ITEM", "AMOUNT"] + COLUMNS
    existing_rows: list[list] = []
    if EXISTING_CB.exists():
        with EXISTING_CB.open(newline="", encoding="utf-8-sig") as f:
            reader = csv.reader(f)
            next(reader, None)
            existing_rows = [r for r in reader if r and r[0] != "DATE"]
    catchup = [row_to_csv(r) for r in sorted(rows, key=lambda x: (x.when, x.voucher_seq))]
    combined = sorted(existing_rows + catchup, key=lambda r: r[0] or "")
    with out_path.open("w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(header)
        w.writerows(combined)


def csv_to_xlsx(csv_path: Path, xlsx_path: Path) -> None:
    import openpyxl

    wb = openpyxl.Workbook()
    ws = wb.active
    with csv_path.open(newline="", encoding="utf-8") as f:
        for r, row in enumerate(csv.reader(f)):
            for c, val in enumerate(row):
                ws.cell(row=r + 1, column=c + 1, value=val)
    wb.save(xlsx_path)


def main() -> None:
    new_totals = category_totals(rows)
    paths = {
        "catchup": OUT_DIR / "audit-catchup-2025.csv",
        "summary": OUT_DIR / "audit-catchup-2025-summary.csv",
        "tb": OUT_DIR / "trial-balance-adjustments-2025.csv",
        "merged": OUT_DIR / "cash-book-2025-with-audit-draft.csv",
    }
    write_catchup_csv(paths["catchup"])
    write_summary(paths["summary"], new_totals)
    write_tb_adjustments(paths["tb"], new_totals)
    merge_cashbooks(paths["merged"])

    csv_to_xlsx(paths["catchup"], OUT_DIR / "audit-catchup-2025.xlsx")
    csv_to_xlsx(paths["merged"], OUT_DIR / "cash-book-2025-with-audit-draft.xlsx")
    csv_to_xlsx(paths["tb"], OUT_DIR / "trial-balance-adjustments-2025.xlsx")

    print("LOCAL audit files generated (no ERP / production changes):\n")
    for p in paths.values():
        print(f"  {p}")
    print(f"  {OUT_DIR / 'audit-catchup-2025.xlsx'}")
    print(f"  {OUT_DIR / 'cash-book-2025-with-audit-draft.xlsx'}")
    print(f"  {OUT_DIR / 'trial-balance-adjustments-2025.xlsx'}")
    print("\nCatch-up by category:")
    for cat, amt in sorted(new_totals.items(), key=lambda x: -x[1]):
        print(f"  {cat:22} {kes(amt):>12,}")
    print(f"\n  {'TOTAL':22} {sum(new_totals.values()):>12,}")


if __name__ == "__main__":
    main()
