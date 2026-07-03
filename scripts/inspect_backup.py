import gzip
import re
import sys
from collections import Counter

path = sys.argv[1] if len(sys.argv) > 1 else r"C:\Users\Admin\Downloads\backup_mysql_20260630_210009.sql.gz"
tables = Counter()
exp_lines_batches = 0
imports_batches = 0
direction_in = 0
direction_out = 0
payments_batches = 0
mpesa_c2b_batches = 0
bank_stmt_batches = 0

with gzip.open(path, "rt", encoding="utf-8", errors="replace") as f:
    for line in f:
        m = re.match(r"INSERT INTO `([^`]+)`", line)
        if m:
            tables[m.group(1)] += 1
            name = m.group(1)
            if name == "expense_statement_lines":
                exp_lines_batches += 1
                direction_in += line.count("'in'")
                direction_out += line.count("'out'")
            elif name == "expense_statement_imports":
                imports_batches += 1
            elif name == "payments":
                payments_batches += 1
            elif name == "mpesa_c2b_transactions":
                mpesa_c2b_batches += 1
            elif name == "bank_statement_transactions":
                bank_stmt_batches += 1

print("=== backup inspect ===")
print(f"expense_statement_imports batches: {imports_batches}")
print(f"expense_statement_lines batches: {exp_lines_batches}")
print(f"direction 'in' token count (rough): {direction_in}")
print(f"direction 'out' token count (rough): {direction_out}")
print(f"payments batches: {payments_batches}")
print(f"mpesa_c2b_transactions batches: {mpesa_c2b_batches}")
print(f"bank_statement_transactions batches: {bank_stmt_batches}")
print("relevant tables:")
for t in sorted(tables):
    if any(k in t for k in ("expense", "payment", "mpesa", "bank_statement", "invoice")):
        print(f"  {t}: {tables[t]} insert batches")
