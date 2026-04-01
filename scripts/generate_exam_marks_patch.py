import argparse
import pathlib
import re
from typing import Dict, List, Tuple


def extract_table_rows(path: pathlib.Path, table: str) -> dict[int, tuple[str, ...]]:
    """
    Parse mysqldump-style single-line INSERTs:
      INSERT INTO `table` VALUES (...),(...);

    Returns rows keyed by integer id (first column), with raw SQL-literal tokens as strings.
    """
    rows: dict[int, tuple[str, ...]] = {}
    ins_prefix = f"INSERT INTO `{table}` VALUES "

    with path.open("r", encoding="utf-8", errors="ignore") as f:
        for line in f:
            if ins_prefix not in line:
                continue

            s = line.strip()
            s = s[s.index("VALUES ") + 7 :]
            if s.endswith(";"):
                s = s[:-1]

            tuples: list[str] = []
            buf: list[str] = []
            depth = 0
            in_str = False
            esc = False

            for ch in s:
                buf.append(ch)
                if in_str:
                    if esc:
                        esc = False
                    elif ch == "\\":
                        esc = True
                    elif ch == "'":
                        in_str = False
                    continue

                if ch == "'":
                    in_str = True
                    continue
                if ch == "(":
                    depth += 1
                elif ch == ")":
                    depth -= 1
                    if depth == 0:
                        tup = "".join(buf).strip()
                        tup = tup.lstrip(",").strip()
                        tuples.append(tup)
                        buf = []

            for t in tuples:
                if not t:
                    continue
                t = t.lstrip(",").strip()
                if not t or t[0] != "(" or t[-1] != ")":
                    continue
                inner = t[1:-1]

                vals: list[str] = []
                cur: list[str] = []
                in_str = False
                esc = False

                for ch in inner:
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
                        continue
                    if ch == ",":
                        vals.append("".join(cur).strip())
                        cur = []
                    else:
                        cur.append(ch)

                vals.append("".join(cur).strip())
                if not vals:
                    continue

                try:
                    _id = int(vals[0])
                except Exception:
                    continue

                rows[_id] = tuple(vals)

    return rows


def parse_create_table_columns(dump_path: pathlib.Path, table: str) -> List[str]:
    """
    Extract column order from a mysqldump CREATE TABLE statement.
    """
    create_re = re.compile(rf"^CREATE TABLE `{re.escape(table)}` \($", re.IGNORECASE)
    col_re = re.compile(r"^\s*`([^`]+)`\s+")

    columns: List[str] = []
    in_table = False
    with dump_path.open("r", encoding="utf-8", errors="ignore") as f:
        for line in f:
            if not in_table:
                if create_re.match(line.rstrip("\n")):
                    in_table = True
                continue

            if line.startswith(")"):
                break

            m = col_re.match(line)
            if m:
                columns.append(m.group(1))

    if not columns:
        raise RuntimeError(f"Could not parse columns for `{table}` from {dump_path}")

    return columns


def diff_rows(before: Dict[int, Tuple[str, ...]], after: Dict[int, Tuple[str, ...]]):
    b_ids = set(before)
    a_ids = set(after)
    added = sorted(a_ids - b_ids)
    changed = sorted([i for i in (a_ids & b_ids) if before[i] != after[i]])
    return added, changed


def sql_ident(name: str) -> str:
    return "`" + name.replace("`", "``") + "`"


def main() -> None:
    ap = argparse.ArgumentParser(description="Generate SQL patch to sync exam_marks between two dumps.")
    ap.add_argument("--before", required=True, help="Path to older SQL dump")
    ap.add_argument("--after", required=True, help="Path to newer SQL dump (target)")
    ap.add_argument("--out", required=True, help="Output SQL patch path")
    args = ap.parse_args()

    before_path = pathlib.Path(args.before)
    after_path = pathlib.Path(args.after)
    out_path = pathlib.Path(args.out)

    table = "exam_marks"

    cols = parse_create_table_columns(after_path, table)
    b_rows = extract_table_rows(before_path, table)
    a_rows = extract_table_rows(after_path, table)

    added_ids, changed_ids = diff_rows(b_rows, a_rows)

    # Validate row width matches column count (mysqldump should)
    sample = next(iter(a_rows.values()), None)
    if sample and len(sample) != len(cols):
        raise RuntimeError(
            f"Column count mismatch for `{table}`: create table has {len(cols)}, "
            f"insert rows have {len(sample)}"
        )

    col_list = ", ".join(sql_ident(c) for c in cols)

    # Build ON DUPLICATE KEY UPDATE clause for idempotence
    update_list = ", ".join(f"{sql_ident(c)}=VALUES({sql_ident(c)})" for c in cols[1:])

    lines: List[str] = []
    lines.append("-- Auto-generated patch: sync exam_marks to AFTER dump")
    lines.append(f"-- before: {before_path}")
    lines.append(f"-- after:  {after_path}")
    lines.append("")
    lines.append("SET FOREIGN_KEY_CHECKS=0;")
    lines.append("START TRANSACTION;")
    lines.append("")

    def emit_upsert(row: Tuple[str, ...]) -> None:
        values = ", ".join(row)
        lines.append(f"INSERT INTO `{table}` ({col_list}) VALUES ({values}) ON DUPLICATE KEY UPDATE {update_list};")

    for _id in added_ids:
        emit_upsert(a_rows[_id])

    for _id in changed_ids:
        emit_upsert(a_rows[_id])

    lines.append("")
    lines.append("COMMIT;")
    lines.append("SET FOREIGN_KEY_CHECKS=1;")
    lines.append("")
    lines.append(f"-- added: {len(added_ids)}")
    lines.append(f"-- changed: {len(changed_ids)}")

    out_path.write_text("\n".join(lines), encoding="utf-8")

    print(f"Wrote {out_path} (added={len(added_ids)}, changed={len(changed_ids)})")


if __name__ == "__main__":
    main()

