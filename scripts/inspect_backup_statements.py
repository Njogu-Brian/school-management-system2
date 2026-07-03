import gzip
import re
import sys

path = sys.argv[1] if len(sys.argv) > 1 else r"C:\Users\Admin\Downloads\backup_mysql_20260630_210009.sql.gz"

# Count expense_statement_lines rows and direction breakdown from INSERT tuples.
# Format: (id,import_id,receipt_no,...,withdrawn,paid_in,direction,...)
in_count = 0
out_count = 0
paid_in_total = 0.0
withdrawn_total = 0.0
loan_in = 0
loan_keywords = ("loan", "fuliza", "m-shwari", "mshwari", "tala", "zenka", "branch", "overdraft", "credit")

with gzip.open(path, "rt", encoding="utf-8", errors="replace") as f:
    for line in f:
        if "INSERT INTO `expense_statement_lines`" not in line:
            continue
        # split on ),( to get row chunks - rough but works for counts
        body = line.split("VALUES", 1)[-1]
        chunks = re.split(r"\),\(", body)
        for chunk in chunks:
            chunk = chunk.strip("(); \n")
            # direction is typically quoted 'in' or 'out' near paid_in amounts
            if ",1,'in'," in chunk or ",'in'," in chunk:
                in_count += 1
            elif ",0,'out'," in chunk or ",'out'," in chunk:
                out_count += 1
            low = chunk.lower()
            if any(k in low for k in loan_keywords) and ("'in'" in chunk or ",1,'in'" in chunk):
                loan_in += 1

print(f"expense_statement_lines ~in rows: {in_count}")
print(f"expense_statement_lines ~out rows: {out_count}")
print(f"loan-related money-in rows (keyword match): {loan_in}")
