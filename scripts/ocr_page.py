#!/usr/bin/env python3
import sys
from pathlib import Path

try:
    import pdfplumber
    import pytesseract
except ImportError as exc:
    sys.stderr.write(f"Missing OCR dependency: {exc}\n")
    sys.exit(2)


def main() -> None:
    if len(sys.argv) < 4:
        sys.stderr.write(
            "Usage: ocr_page.py <pdf_path> <page_number> <language> "
            "[tesseract_path] [resolution]\n"
        )
        sys.exit(1)

    pdf_path = Path(sys.argv[1]).resolve()
    if not pdf_path.exists():
        sys.stderr.write(f"PDF not found: {pdf_path}\n")
        sys.exit(1)

    try:
        page_number = max(1, int(sys.argv[2]))
    except ValueError:
        sys.stderr.write("Page number must be an integer\n")
        sys.exit(1)

    language = sys.argv[3]

    if len(sys.argv) >= 5:
        pytesseract.pytesseract.tesseract_cmd = sys.argv[4]

    resolution = 220
    if len(sys.argv) >= 6:
        try:
            resolution = max(150, int(sys.argv[5]))
        except ValueError:
            pass

    with pdfplumber.open(pdf_path) as pdf:
        total_pages = len(pdf.pages)
        if total_pages == 0:
            sys.stderr.write("The PDF does not contain any pages\n")
            sys.exit(1)

        index = min(page_number - 1, total_pages - 1)
        page = pdf.pages[index]
        image = page.to_image(resolution=resolution).original
        text = pytesseract.image_to_string(image, lang=language)
        sys.stdout.write(text)


if __name__ == "__main__":
    main()

