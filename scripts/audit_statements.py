"""
One-off forensic audit of the latest DB backup.

Goals:
  1. Confirm whether any statement PDF passwords are stored anywhere.
  2. Audit categorisation of expense_statement_lines & expenses, focusing on
     the Equity paybill 247247 -> own account transfers wrongly tagged as Electricity.
"""
import re
import sys
import json
from collections import defaultdict, Counter

SQL = r"E:\school-management-system2\school-management-system2\storage\app\backup_latest.sql"


def iter_table_inserts(path, table):
    """Yield raw VALUES (...) tuples (as strings) for a given table."""
    prefix = f"INSERT INTO `{table}` VALUES "
    with open(path, "r", encoding="utf-8", errors="replace") as fh:
        for line in fh:
            if line.startswith(prefix):
                yield line[len(prefix):].rstrip().rstrip(";")


def split_rows(values_blob):
    """Split a mysqldump VALUES blob into individual row strings, respecting quotes."""
    rows = []
    depth = 0
    in_str = False
    esc = False
    cur = []
    for ch in values_blob:
        if in_str:
            cur.append(ch)
            if esc:
                esc = False
            elif ch == "\\":
                esc = True
            elif ch == "'":
                in_str = False
            continue
        if ch == "'":
            in_str = True
            cur.append(ch)
        elif ch == "(":
            if depth == 0:
                cur = []
            else:
                cur.append(ch)
            depth += 1
        elif ch == ")":
            depth -= 1
            if depth == 0:
                rows.append("".join(cur))
            else:
                cur.append(ch)
        else:
            cur.append(ch)
    return rows


def split_fields(row):
    """Split a single row string into field values."""
    fields = []
    in_str = False
    esc = False
    cur = []
    for ch in row:
        if in_str:
            if esc:
                cur.append(ch)
                esc = False
            elif ch == "\\":
                cur.append(ch)
                esc = True
            elif ch == "'":
                in_str = False
            else:
                cur.append(ch)
            continue
        if ch == "'":
            in_str = True
        elif ch == ",":
            fields.append("".join(cur))
            cur = []
        else:
            cur.append(ch)
    fields.append("".join(cur))
    return [f.strip() for f in fields]


def get_columns(path, table):
    cols = []
    capture = False
    with open(path, "r", encoding="utf-8", errors="replace") as fh:
        for line in fh:
            if line.startswith(f"CREATE TABLE `{table}`"):
                capture = True
                continue
            if capture:
                m = re.match(r"\s+`([a-zA-Z0-9_]+)`\s", line)
                if m:
                    cols.append(m.group(1))
                elif line.startswith(")") or line.strip().startswith("PRIMARY KEY") or line.strip().startswith("KEY") or line.strip().startswith("UNIQUE") or line.strip().startswith("CONSTRAINT"):
                    break
    return cols


def load_table(path, table):
    cols = get_columns(path, table)
    out = []
    for blob in iter_table_inserts(path, table):
        for row in split_rows(blob):
            fields = split_fields(row)
            if len(fields) == len(cols):
                out.append(dict(zip(cols, fields)))
            else:
                out.append({"__cols__": cols, "__raw__": fields})
    return cols, out


def main():
    target = sys.argv[1] if len(sys.argv) > 1 else "all"

    if target in ("schema", "all"):
        for t in ["expense_statement_lines", "expenses", "expense_lines"]:
            print(f"### {t} columns ###")
            print(get_columns(SQL, t))
            print()

    if target in ("lines", "all"):
        cols, rows = load_table(SQL, "expense_statement_lines")
        print(f"expense_statement_lines: {len(rows)} rows, cols={cols}\n")

    main_audit()


def NV(v):
    return None if v == "NULL" else v


def main_audit():
    cols, rows = load_table(SQL, "expense_statement_lines")
    rows = [r for r in rows if "__raw__" not in r]
    print(f"Loaded {len(rows)} statement lines. Columns:\n{cols}\n")

    # Category id 35 = Electricity
    def cat(r):
        return NV(r.get("expense_category_id"))

    # Find narration / description column names
    text_cols = [c for c in cols if c in ("narration", "raw_narration", "description",
                                          "expense_description", "recipient_name", "vendor_name",
                                          "paybill_number", "account_reference", "transaction_type",
                                          "display_name", "direction", "review_status",
                                          "withdrawn_amount", "paid_in_amount")]
    print("Inspecting columns:", text_cols, "\n")

    elec = [r for r in rows if cat(r) == "35"]
    print(f"Lines categorised as Electricity (35): {len(elec)}")

    def blob(r):
        return " ".join(str(r.get(c) or "") for c in text_cols).upper()

    # Among electricity lines, how many reference Equity paybill 247247 / own acct / transfers / loans
    pat_247 = sum(1 for r in elec if "247247" in blob(r))
    pat_equity = sum(1 for r in elec if "EQUITY" in blob(r))
    pat_own = sum(1 for r in elec if "0120263149140" in blob(r))
    pat_transfer = sum(1 for r in elec if "TRANSFER" in blob(r))
    pat_fuliza = sum(1 for r in elec if "FULIZA" in blob(r))
    print(f"  ...containing 247247: {pat_247}")
    print(f"  ...containing EQUITY: {pat_equity}")
    print(f"  ...containing own acct 0120263149140: {pat_own}")
    print(f"  ...containing TRANSFER: {pat_transfer}")
    print(f"  ...containing FULIZA: {pat_fuliza}")

    # Show distinct narration-ish samples for electricity lines
    print("\nSample Electricity-categorised lines (up to 25):")
    seen = set()
    n = 0
    for r in elec:
        key = (r.get("paybill_number"), r.get("recipient_name"), r.get("account_reference"))
        if key in seen:
            continue
        seen.add(key)
        print(f"  id={r.get('id')} type={r.get('transaction_type')} pb={r.get('paybill_number')} "
              f"rec={r.get('recipient_name')!r} acc={r.get('account_reference')!r} "
              f"desc={r.get('expense_description')!r} amt={r.get('withdrawn_amount')} status={r.get('review_status')}")
        n += 1
        if n >= 25:
            break

    # All lines hitting paybill 247247 regardless of category
    p247 = [r for r in rows if (r.get("paybill_number") == "247247") or ("247247" in blob(r))]
    print(f"\n\nAll lines referencing paybill 247247: {len(p247)}")
    catcount = Counter(cat(r) for r in p247)
    print("  category_id distribution:", dict(catcount))
    dircount = Counter(r.get("direction") for r in p247)
    print("  direction distribution:", dict(dircount))
    # account references seen for 247247
    accs = Counter((r.get("account_reference") or "")[:40] for r in p247)
    print("  top account_references:", accs.most_common(15))
    total_247 = 0.0
    for r in p247:
        try:
            total_247 += float(r.get("withdrawn_amount") or 0)
        except ValueError:
            pass
    print(f"  total withdrawn via 247247: {total_247:,.2f}")

    # Distribution of category among ALL lines
    print("\nTop categories across all statement lines:")
    allcat = Counter(cat(r) for r in rows)
    for cid, cnt in allcat.most_common(20):
        print(f"  cat {cid}: {cnt}")

    # --- DEEP DIVE 1: Electricity lines split by KPLC vs not ---
    print("\n\n===== DEEP DIVE: Electricity (35) line quality =====")
    kplc = [r for r in elec if (r.get("paybill_number") in ("888880", "888888"))
            or "KPLC" in blob(r) or "KENYA POWER" in blob(r)]
    nonkplc = [r for r in elec if r not in kplc]
    print(f"  KPLC/Kenya Power electricity lines (plausibly correct): {len(kplc)}")
    print(f"  NON-KPLC lines tagged Electricity (suspicious): {len(nonkplc)}")
    susp_paybills = Counter((r.get("paybill_number"), r.get("recipient_name")) for r in nonkplc)
    print("  Suspicious paybill/recipient combos tagged Electricity:")
    for combo, cnt in susp_paybills.most_common(25):
        print(f"    {combo}: {cnt}")

    # --- DEEP DIVE 2: the 247247 Electricity lines in full ---
    print("\n===== Electricity lines that are actually Equity paybill 247247 =====")
    for r in [r for r in elec if "247247" in blob(r)]:
        print(f"  line_id={r.get('id')} import={r.get('import_id')} amt={r.get('withdrawn_amount')} "
              f"acc={r.get('account_reference')!r} expense_id={r.get('expense_id')} "
              f"status={r.get('review_status')}\n     narration={r.get('narration')!r}")

    # --- DEEP DIVE 3: all 247247 -> own Equity account (internal transfers) ---
    own = ("0120263149140", "149140")
    print("\n===== 247247 lines depositing into OWN Equity account 0120263149140 =====")
    own_lines = [r for r in rows if r.get("paybill_number") == "247247"
                 and (r.get("account_reference") in own)]
    tot = 0.0
    catc = Counter()
    for r in own_lines:
        try:
            tot += float(r.get("withdrawn_amount") or 0)
        except ValueError:
            pass
        catc[cat(r)] += 1
    print(f"  count={len(own_lines)}  total={tot:,.2f}")
    print(f"  category distribution of these internal transfers: {dict(catc)}")
    print(f"  expense_ids attached: {sorted(set(NV(r.get('expense_id')) for r in own_lines if NV(r.get('expense_id'))))[:40]}")


def expense_audit():
    cols, rows = load_table(SQL, "expenses")
    rows = [r for r in rows if "__raw__" not in r]
    print(f"\n\n########## EXPENSES TABLE: {len(rows)} rows ##########")
    print("cols:", cols)
    # find col indexes
    def g(r, name):
        return NV(r.get(name))
    # category col name?
    catcol = "expense_category_id" if "expense_category_id" in cols else (
        "category_id" if "category_id" in cols else None)
    print("category column:", catcol)

    # Expense 1351
    e = next((r for r in rows if r.get("id") == "1351"), None)
    print("\n--- Expense id 1351 ---")
    if e:
        for k, v in e.items():
            print(f"  {k} = {v}")
    else:
        print("  not found")

    # All electricity expenses
    if catcol:
        elec = [r for r in rows if g(r, catcol) == "35"]
        print(f"\nExpenses with category 35 (Electricity): {len(elec)}")
        for r in elec[:60]:
            print(f"  id={r.get('id')} amt={r.get('amount') or r.get('total') or r.get('total_amount')} "
                  f"desc={(g(r,'description') or g(r,'title') or '')[:80]!r} status={g(r,'status')}")


def extra():
    cols, rows = load_table(SQL, "expense_statement_lines")
    rows = [r for r in rows if "__raw__" not in r]

    def cat(r):
        return NV(r.get("expense_category_id"))

    # 494 suspicious electricity lines (NULL recipient) - what are they really?
    elec = [r for r in rows if cat(r) == "35"]
    susp = [r for r in elec if not NV(r.get("paybill_number")) and not NV(r.get("recipient_name"))]
    print(f"\n\n===== {len(susp)} 'Electricity' lines with NULL paybill+recipient =====")
    typec = Counter(r.get("transaction_type") for r in susp)
    print("  transaction_type dist:", dict(typec))
    statusc = Counter(r.get("review_status") for r in susp)
    print("  review_status dist:", dict(statusc))
    tot = sum(float(r.get("withdrawn_amount") or 0) for r in susp)
    print(f"  total amount: {tot:,.2f}")
    print("  sample narrations:")
    for r in susp[:20]:
        nar = (r.get("narration") or "").replace("\\n", " ")
        print(f"    id={r.get('id')} amt={r.get('withdrawn_amount')} exp={r.get('expense_id')} :: {nar[:90]}")

    # ALL deposits into own Equity account 0120263149140 (match in narration OR account_reference)
    own = [r for r in rows if "0120263149140" in (r.get("narration") or "")
           or r.get("account_reference") in ("0120263149140", "149140")]
    tot_own = sum(float(r.get("withdrawn_amount") or 0) for r in own)
    became_exp = [r for r in own if NV(r.get("expense_id"))]
    tot_exp = sum(float(r.get("withdrawn_amount") or 0) for r in became_exp)
    print(f"\n===== ALL transfers into OWN Equity acct 0120263149140 =====")
    print(f"  total lines={len(own)} total_amount={tot_own:,.2f}")
    print(f"  of these, wrongly turned into EXPENSES: {len(became_exp)} totalling {tot_exp:,.2f}")
    print(f"  their category ids: {Counter(cat(r) for r in became_exp)}")
    print(f"  expense_ids: {sorted(set(int(NV(r.get('expense_id'))) for r in became_exp))}")


def groupcheck():
    cols, rows = load_table(SQL, "expense_statement_lines")
    rows = [r for r in rows if "__raw__" not in r]
    def cat(r):
        return NV(r.get("expense_category_id"))
    elec = [r for r in rows if cat(r) == "35"]
    susp = [r for r in elec if not NV(r.get("paybill_number")) and not NV(r.get("recipient_name"))]
    gk = Counter(r.get("group_key") for r in susp)
    print("\n===== group_key of the 510 mislabelled 'Electricity' lines =====")
    print("  distinct group_keys:", len(gk))
    for k, c in gk.most_common(5):
        print(f"    {k} -> {c} lines")
    # How many Fuliza paybill lines lost their paybill number entirely?
    fuliza_paybill = [r for r in rows if r.get("transaction_type") == "paybill"
                      and "FULIZA" in (r.get("narration") or "").upper()]
    lost = [r for r in fuliza_paybill if not NV(r.get("paybill_number"))]
    print(f"\n  Fuliza-funded paybill lines: {len(fuliza_paybill)}; "
          f"with paybill_number LOST (NULL): {len(lost)}")
    gk2 = Counter(r.get("group_key") for r in lost)
    print(f"  these collapse into {len(gk2)} group(s); top: {gk2.most_common(3)}")
    tot = sum(float(r.get('withdrawn_amount') or 0) for r in lost)
    print(f"  total value of the collapsed group: {tot:,.2f}")
    cats = Counter(cat(r) for r in lost)
    print(f"  current category spread of collapsed group: {dict(cats)}")


if __name__ == "__main__":
    main_audit()
    expense_audit()
    extra()
    groupcheck()
