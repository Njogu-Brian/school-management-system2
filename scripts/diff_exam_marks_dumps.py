import json
import pathlib


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
                        # mysqldump separates tuples with a leading comma: ",(....)"
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


def diff_rows(before: dict[int, tuple[str, ...]], after: dict[int, tuple[str, ...]]):
    b_ids = set(before)
    a_ids = set(after)
    added = sorted(a_ids - b_ids)
    removed = sorted(b_ids - a_ids)
    common = a_ids & b_ids
    changed = sorted([i for i in common if before[i] != after[i]])
    return added, removed, changed


def main():
    import argparse

    ap = argparse.ArgumentParser()
    ap.add_argument("--before", required=True, help="Path to older SQL dump")
    ap.add_argument("--after", required=True, help="Path to newer SQL dump")
    ap.add_argument("--out-dir", default=".", help="Output directory")
    args = ap.parse_args()

    before_path = pathlib.Path(args.before)
    after_path = pathlib.Path(args.after)
    out_dir = pathlib.Path(args.out_dir)
    out_dir.mkdir(parents=True, exist_ok=True)

    b_marks = extract_table_rows(before_path, "exam_marks")
    a_marks = extract_table_rows(after_path, "exam_marks")

    added, removed, changed = diff_rows(b_marks, a_marks)

    changed_details = []
    for i in changed:
        bv = b_marks[i]
        av = a_marks[i]
        diffs = [idx for idx, (x, y) in enumerate(zip(bv, av)) if x != y]
        changed_details.append((i, diffs))

    summary = {
        "exam_marks": {
            "before_count": len(b_marks),
            "after_count": len(a_marks),
            "added_total": len(added),
            "removed_total": len(removed),
            "changed_total": len(changed),
            "added_ids_sample": added[:50],
            "removed_ids_sample": removed[:50],
            "changed_ids_sample": changed[:50],
            "changed_sample": [
                {"id": i, "changed_value_indexes": diffs}
                for i, diffs in changed_details[:20]
            ],
        }
    }

    (out_dir / "exam_marks_diff_summary.json").write_text(
        json.dumps(summary, indent=2), encoding="utf-8"
    )
    (out_dir / "exam_marks_changed_ids.csv").write_text(
        "id,changed_value_indexes\n"
        + "\n".join(f'{i},"{",".join(map(str, diffs))}"' for i, diffs in changed_details),
        encoding="utf-8",
    )

    print(json.dumps(summary, indent=2))


if __name__ == "__main__":
    main()

