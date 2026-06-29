#!/usr/bin/env python3
"""
M-Pesa personal statement parser for expense tracking.
Extracts transactions from password-protected Safaricom M-PESA DETAILED STATEMENT PDFs.
"""

import argparse
import json
import re
import sys
from datetime import datetime
from pathlib import Path
from typing import Optional

try:
    import pdfplumber
except ImportError:
    print("Error: pdfplumber not installed. Install with: pip install pdfplumber", file=sys.stderr)
    sys.exit(1)

# Reuse battle-tested table parsing from the equity parser module (same M-Pesa layout).
SCRIPT_DIR = Path(__file__).resolve().parent
sys.path.insert(0, str(SCRIPT_DIR))

from equity_statement_parser import (  # noqa: E402
    parse_paybill_from_text,
    parse_paybill_table,
)


def extract_metadata(full_text: str) -> dict:
    meta = {}
    if not full_text:
        return meta

    patterns = {
        'customer_name': r'Customer Name:\s*(.+)',
        'mobile_number': r'Mobile Number:\s*(\S+)',
        'email': r'Email Address:\s*(\S+)',
        'statement_period': r'Statement Period:\s*(.+)',
        'request_date': r'Request Date:\s*(.+)',
    }
    for key, pattern in patterns.items():
        match = re.search(pattern, full_text, re.IGNORECASE)
        if match:
            meta[key] = match.group(1).strip()

    period = meta.get('statement_period', '')
    period_match = re.search(
        r'(\d{1,2}\s+\w+\s+\d{4})\s*-\s*(\d{1,2}\s+\w+\s+\d{4})',
        period,
        re.IGNORECASE,
    )
    if period_match:
        meta['period_start'] = _parse_human_date(period_match.group(1))
        meta['period_end'] = _parse_human_date(period_match.group(2))

    return meta


def _parse_human_date(value: str) -> str | None:
    value = (value or '').strip()
    for fmt in ('%d %b %Y', '%d %B %Y'):
        try:
            return datetime.strptime(value, fmt).strftime('%Y-%m-%d')
        except ValueError:
            continue
    return None


def _looks_like_mpesa(text_upper: str) -> bool:
    return (
        ('RECEIPT' in text_upper and 'WITHDRAWN' in text_upper)
        or ('DETAILED STATEMENT' in text_upper and 'COMPLETION' in text_upper)
        or ('RECEIPT NO' in text_upper and 'PAID IN' in text_upper)
    )


def count_pages(pdf_path: str, password: Optional[str] = None) -> dict:
    """Cheaply open the PDF to get the page count + a light M-Pesa check.

    Validates the password and bounds memory by only reading text from the
    first few pages (no table extraction).
    """
    open_kwargs = {}
    if password:
        open_kwargs['password'] = password

    try:
        with pdfplumber.open(pdf_path, **open_kwargs) as pdf:
            total = len(pdf.pages)
            is_mpesa = False
            for page in pdf.pages[:3]:
                text = page.extract_text() or ''
                if _looks_like_mpesa(text.upper()):
                    is_mpesa = True
                try:
                    page.flush_cache()
                except Exception:
                    pass
                if is_mpesa:
                    break
            return {'page_count': total, 'is_mpesa': is_mpesa}
    except Exception as exc:
        message = str(exc).lower()
        if 'password' in message or 'encrypted' in message or 'decrypt' in message:
            return {'error': 'password_required', 'message': str(exc)}
        return {'error': 'parse_failed', 'message': str(exc)}


def extract_mpesa_pages(
    pdf_path: str,
    password: Optional[str] = None,
    start_page: Optional[int] = None,
    end_page: Optional[int] = None,
) -> dict:
    """Extract M-Pesa transaction tables.

    When start_page/end_page are given (1-based, inclusive) only that slice of
    pages is processed, and each page's cache is flushed immediately after use
    so peak memory stays bounded to a small batch of pages instead of the whole
    document. This is what lets the importer parse ~5 pages at a time without
    exhausting server memory.
    """
    pages_content = []
    is_mpesa_statement = False
    full_text_parts = []

    open_kwargs = {}
    if password:
        open_kwargs['password'] = password

    try:
        with pdfplumber.open(pdf_path, **open_kwargs) as pdf:
            total_pages = len(pdf.pages)
            lo = max(1, start_page or 1)
            hi = min(total_pages, end_page or total_pages)

            for page_index in range(lo, hi + 1):
                page = pdf.pages[page_index - 1]
                text = page.extract_text() or ''
                full_text_parts.append(text)
                text_upper = text.upper()
                if _looks_like_mpesa(text_upper):
                    is_mpesa_statement = True

                page_data = {
                    'page_number': page_index,
                    'text': text or '',
                    'tables': [],
                }

                tables = page.extract_tables() or []
                has_header = any(
                    t and len(t) > 0 and any(
                        col and (
                            'Receipt' in str(col)
                            or 'Withdrawn' in str(col)
                            or ('Paid' in str(col) and 'In' in str(col))
                        )
                        for col in (t[0] if t else [])
                    )
                    for t in tables
                )

                if not tables or not has_header:
                    for strategy in (
                        {'vertical_strategy': 'lines', 'horizontal_strategy': 'lines'},
                        {'vertical_strategy': 'text', 'horizontal_strategy': 'text'},
                    ):
                        try:
                            alt = page.extract_tables(strategy)
                            if alt and any(t and len(t) > 1 for t in alt):
                                tables = alt
                                break
                        except Exception:
                            pass

                for table in tables or []:
                    if not table or len(table) == 0:
                        continue
                    header_row = table[0]
                    header_ok = any(
                        col and (
                            'Receipt' in str(col)
                            or 'Withdrawn' in str(col)
                            or ('Paid' in str(col) and 'In' in str(col))
                        )
                        for col in header_row
                    )
                    if header_ok:
                        page_data['tables'].append({
                            'page_number': page_index,
                            'header': header_row,
                            'rows': table[1:] if len(table) > 1 else [],
                        })
                    else:
                        data_rows = [row for row in table if row and len(row) >= 2]
                        found_header_idx = None
                        for ri, row in enumerate(data_rows[:10]):
                            has_receipt = any(col and 'Receipt' in str(col) for col in row)
                            has_amount = any(
                                col and (
                                    ('Paid' in str(col) and 'In' in str(col))
                                    or 'Withdrawn' in str(col)
                                )
                                for col in row
                            )
                            if has_receipt and has_amount:
                                found_header_idx = ri
                                break
                        if found_header_idx is not None:
                            page_data['tables'].append({
                                'page_number': page_index,
                                'header': data_rows[found_header_idx],
                                'rows': data_rows[found_header_idx + 1:],
                            })

                pages_content.append(page_data)

                # Free this page's cached layout immediately so memory stays
                # bounded to the current batch rather than the whole PDF.
                try:
                    page.flush_cache()
                    page.get_textmap.cache_clear()
                except Exception:
                    pass

    except Exception as exc:
        message = str(exc).lower()
        if 'password' in message or 'encrypted' in message or 'decrypt' in message:
            return {'error': 'password_required', 'message': str(exc)}
        print(f"Error extracting PDF: {exc}", file=sys.stderr)
        return None

    return {
        'pages': pages_content,
        'is_mpesa': is_mpesa_statement,
        'full_text': '\n'.join(full_text_parts),
        'total_pages': total_pages,
    }


def main():
    parser = argparse.ArgumentParser(description='Parse M-Pesa statement PDF for expense tracking')
    parser.add_argument('pdf_path', help='Path to PDF file')
    parser.add_argument('--password', help='PDF password if encrypted', default=None)
    parser.add_argument('--output', help='Optional output JSON file', default=None)
    parser.add_argument('--count-pages', action='store_true',
                        help='Only return the page count + M-Pesa detection (fast, low memory)')
    parser.add_argument('--start-page', type=int, default=None,
                        help='First page to parse (1-based, inclusive)')
    parser.add_argument('--end-page', type=int, default=None,
                        help='Last page to parse (1-based, inclusive)')
    args = parser.parse_args()

    pdf_path = Path(args.pdf_path)
    if not pdf_path.exists():
        print(json.dumps({'success': False, 'error': 'file_not_found'}))
        sys.exit(1)

    # Fast path: just count pages + verify password / M-Pesa format.
    if args.count_pages:
        info = count_pages(str(pdf_path), args.password)
        if info.get('error') == 'password_required':
            print(json.dumps({'success': False, 'error': 'password_required',
                              'message': info.get('message', 'PDF is password protected')}))
            sys.exit(2)
        if info.get('error'):
            print(json.dumps({'success': False, 'error': info['error'],
                              'message': info.get('message', 'Could not read PDF')}))
            sys.exit(1)
        print(json.dumps({
            'success': True,
            'page_count': info.get('page_count', 0),
            'is_mpesa': info.get('is_mpesa', False),
        }))
        return

    result = extract_mpesa_pages(str(pdf_path), args.password, args.start_page, args.end_page)
    if result is None:
        print(json.dumps({'success': False, 'error': 'parse_failed'}))
        sys.exit(1)

    if result.get('error') == 'password_required':
        print(json.dumps({
            'success': False,
            'error': 'password_required',
            'message': result.get('message', 'PDF is password protected'),
        }))
        sys.exit(2)

    pages = result.get('pages', [])
    tables = []
    for page in pages:
        for table in page.get('tables', []):
            tables.append(table)

    transactions = parse_paybill_table(tables)
    if not transactions and pages:
        page_texts = [page.get('text') or '' for page in pages]
        transactions = parse_paybill_from_text(page_texts)

    metadata = extract_metadata(result.get('full_text', ''))

    payload = {
        'success': True,
        'metadata': metadata,
        'transactions': transactions,
        'transaction_count': len(transactions),
        'is_mpesa': result.get('is_mpesa', False),
        'total_pages': result.get('total_pages', 0),
    }

    output = json.dumps(payload, indent=2)
    if args.output:
        with open(args.output, 'w', encoding='utf-8') as handle:
            handle.write(output)
    else:
        print(output)


if __name__ == '__main__':
    main()
