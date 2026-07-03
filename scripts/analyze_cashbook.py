"""Summarise cash-book-2025 merged CSV vs trial balance benchmarks."""
import csv
import sys
from collections import defaultdict

path = sys.argv[1] if len(sys.argv) > 1 else r"C:\Users\Admin\Downloads\cash-book-2025-merged-v2.csv"
COLUMNS = [
    "FOOD", "Stationary", "Textbooks", "EXAMS", "Uniform", "Communication", "Office Exp",
    "Water", "General repair", "Furniture", "Medical", "Service provider", "Fuel", "NTSA",
    "Trash", "Colnet", "Construction", "Vehicle Repairs", "Transport", "Electricity", "Labour",
    "Donation", "Advance tax", "Insurance", "Lisence", "Assets", "LOAN", "Salary",
    "Valuation & Inspection", "ACTIVITIES", "NSSF", "SHA", "PAYE", "NITA", "Housing", "Rent",
]
TB_2025 = {
    "Vehicle Repairs": 773_079,
    "Food": 1_088_978,
    "LOAN/finance (TB)": 2_770_893,
    "Fuel": 2_132_490,
    "ACTIVITIES": 925_821,
    "NITA/payroll": 5_769_461,
}

cat_totals = defaultdict(float)
loan_items = defaultdict(float)

with open(path, newline="", encoding="utf-8-sig") as f:
    for row in csv.reader(f):
        if len(row) < 4 or row[0] == "DATE":
            continue
        if str(row[0]).strip().upper() == "TOTAL":
            continue
        for i, col in enumerate(COLUMNS):
            idx = 4 + i
            if idx >= len(row) or not row[idx]:
                continue
            try:
                v = float(str(row[idx]).replace(",", ""))
            except ValueError:
                continue
            if v > 0:
                cat_totals[col] += v
                if col == "LOAN":
                    loan_items[row[2] or "unknown"] += v

print("=== Cash book 2025 (merged) ===")
for k, v in sorted(cat_totals.items(), key=lambda x: -x[1]):
    print(f"  {k:22} {v:>14,.2f}")
print(f"  {'TOTAL':22} {sum(cat_totals.values()):>14,.2f}")

print("\n=== vs Trial Balance 2025 (gaps = possible catch-up) ===")
mapping = {
    "Vehicle Repairs": "Vehicle Repairs",
    "FOOD": "Food",
    "LOAN": "LOAN/finance (TB)",
    "Fuel": "Fuel",
    "ACTIVITIES": "ACTIVITIES",
    "NITA": "NITA/payroll",
}
for cb_key, tb_key in mapping.items():
    cb = cat_totals.get(cb_key, 0)
    tb = TB_2025.get(tb_key, 0)
    gap = cb - tb
    print(f"  {cb_key:18} cashbook {cb:>12,.0f}  TB {tb:>12,.0f}  gap {gap:>+12,.0f}")

print("\n=== LOAN column detail (top 15) ===")
for k, v in sorted(loan_items.items(), key=lambda x: -x[1])[:15]:
    print(f"  {k[:55]:55} {v:>12,.2f}")
print(f"  {'TOTAL LOAN column':55} {cat_totals.get('LOAN', 0):>12,.2f}")
