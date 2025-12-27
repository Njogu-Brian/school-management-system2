import sys
import json
import pdfplumber

def main():
    if len(sys.argv) < 2:
        print(json.dumps([]))
        return
    path = sys.argv[1]
    lines = []
    try:
        with pdfplumber.open(path) as pdf:
            for page in pdf.pages:
                text = page.extract_text(layout=True, x_tolerance=1, y_tolerance=1) or ""
                for ln in text.split("\n"):
                    ln = ln.strip()
                    if ln:
                        lines.append(ln)
                try:
                    tables = page.extract_tables()
                except Exception:
                    tables = []
                for table in tables or []:
                    for row in table or []:
                        row_text = " ".join([str(c).strip() for c in row if c is not None]).strip()
                        if row_text:
                            lines.append(row_text)
        print(json.dumps(lines))
    except Exception as e:
        print(json.dumps([]))
        sys.stderr.write(str(e))

if __name__ == "__main__":
    main()

