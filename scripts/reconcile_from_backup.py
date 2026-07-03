"""Extract finance reconciliation stats from production SQL backup (no DB restore needed)."""
import gzip
import re
import sys
from collections import defaultdict

BACKUP = sys.argv[1] if len(sys.argv) > 1 else r"C:\Users\Admin\Downloads\backup_mysql_20260630_210009.sql.gz"
YEAR = int(sys.argv[2]) if len(sys.argv) > 2 else 2025

LOAN_PAYBILLS = {
    "7787614", "589036", "597686", "4135035", "998608", "851900", "979988", "4133807",
}
LOAN_IN_KW = (
    "loan", "fuliza", "m-shwari", "mshwari", "overdraft", "tala", "zenka", "branch",
    "signalwave", "hfm", "tingg", "cellulant", "credit from", "advance", "timiza", "okash",
    "azura", "mycredit", "premier credit", "kcb mpesa", "aventus", "repayment", "loan recovery",
)
LOAN_OUT_KW = LOAN_IN_KW

# One row tuple inside INSERT ... VALUES (...),(...);
ROW_RE = re.compile(
    r"\((\d+),(\d+),'([^']*)','(\d{4}-\d{2}-\d{2} [^']*)','((?:[^'\\]|\\.)*)','[^']*',"
    r"([\d.]+),([\d.]+),'(in|out)','([^']*)',(\d+),"
    r"(?:'((?:[^'\\]|\\.)*)'|NULL),(?:'((?:[^'\\]|\\.)*)'|NULL),(?:'([^']*)'|NULL),"
    r"(?:'([^']*)'|NULL)",
    re.DOTALL,
)


def main() -> None:
    direction_counts = defaultdict(int)
    in_total = out_total = 0.0
    paybill_out = defaultdict(float)
    loan_in_total = loan_out_total = 0.0
    loan_in_lines = loan_out_lines = 0
    send_money_out = 0.0
    send_money_count = 0
    rows_2025 = 0

    with gzip.open(BACKUP, "rt", encoding="utf-8", errors="replace") as f:
        for line in f:
            if "INSERT INTO `expense_statement_lines`" not in line:
                continue
            for m in ROW_RE.finditer(line):
                completed = m.group(4)
                if not completed.startswith(str(YEAR)):
                    continue
                rows_2025 += 1
                narration = m.group(5).replace("\\n", " ").replace("\\'", "'").lower()
                withdrawn = float(m.group(6))
                paid_in = float(m.group(7))
                direction = m.group(8)
                tx_type = m.group(9)
                paybill = m.group(13) or ""

                direction_counts[direction] += 1
                if direction == "in":
                    in_total += paid_in
                    if any(k in narration for k in LOAN_IN_KW):
                        loan_in_total += paid_in
                        loan_in_lines += 1
                else:
                    out_total += withdrawn
                    if tx_type == "send_money":
                        send_money_out += withdrawn
                        send_money_count += 1
                    if paybill in LOAN_PAYBILLS:
                        paybill_out[paybill] += withdrawn
                    if any(k in narration for k in LOAN_OUT_KW):
                        loan_out_total += withdrawn
                        loan_out_lines += 1

    true_cost = max(0.0, loan_out_total - loan_in_total)

    print("=== Production backup - M-Pesa statement lines (%s) ===" % YEAR)
    print("Parsed rows: %s" % f"{rows_2025:,}")
    print("Money IN:  %s rows  KES %s" % (f"{direction_counts.get('in', 0):,}", f"{in_total:,.2f}"))
    print("Money OUT: %s rows  KES %s" % (f"{direction_counts.get('out', 0):,}", f"{out_total:,.2f}"))
    print()
    print("=== Mobile loans (statement evidence) ===")
    print("Loan-related money IN:  %s lines  KES %s" % (f"{loan_in_lines:,}", f"{loan_in_total:,.2f}"))
    print("Loan-related money OUT: %s lines  KES %s" % (f"{loan_out_lines:,}", f"{loan_out_total:,.2f}"))
    print("True loan cost (repaid minus received): KES %s" % f"{true_cost:,.2f}")
    if paybill_out:
        print("By paybill (repayments):")
        for pb, amt in sorted(paybill_out.items(), key=lambda x: -x[1]):
            print("  %s: %s" % (pb, f"{amt:,.2f}"))
    print()
    print("Send-money OUT: %s lines  KES %s" % (f"{send_money_count:,}", f"{send_money_out:,.2f}"))
    print("  (pool for bad-debt review if unrecoverable)")
    print()
    if rows_2025 == 0:
        print("WARNING: no rows parsed - check backup format")
    else:
        print("VERDICT: Money-in is already in expense_statement_lines.")
        print("You do NOT need separate money-in statement uploads if the full-year M-Pesa PDF was imported.")


if __name__ == "__main__":
    main()
