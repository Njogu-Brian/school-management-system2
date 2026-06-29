"""
Parse Azanet internet invoice/receipt .eml files (each carries a PDF) into a
JSON the AzanetInternetBillSeeder can load as expenses.

Usage: python scripts/parse_azanet_emails.py "<downloads_dir>" [out.json]
"""
import sys, os, email, io, re, json
from email import policy
import pdfplumber


def first_pdf(path):
    with open(path, 'rb') as f:
        msg = email.message_from_binary_file(f, policy=policy.default)
    for part in msg.walk():
        fn = part.get_filename()
        if fn and fn.lower().endswith('.pdf'):
            data = part.get_payload(decode=True)
            if data:
                return fn, data
    return None, None


def num(s):
    return float(re.sub(r'[^\d.]', '', s)) if s else 0.0


def parse_invoice(txt):
    number = (re.search(r'Number:\s*(\d{6,})', txt) or [None, None])[1]
    date = (re.search(r'Date:\s*(\d{2}/\d{2}/\d{4})', txt) or [None, None])[1]
    total = (re.search(r'TOTAL:\s*KSH\s*([\d,\.]+)', txt, re.I)
             or re.search(r'Total due:\s*Ksh\s*([\d,\.]+)', txt, re.I) or [None, None])[1]
    plan = (re.search(r'\n([A-Za-z0-9 ]*Mbps[A-Za-z0-9 ]*)', txt) or [None, None])[1]
    period = (re.search(r'\((\d{2}/\d{2}/\d{4}\s*-\s*\d{2}/\d{2}/\d{4})\)', txt) or [None, None])[1]
    acct = (re.search(r'Account:\s*(\d+)', txt) or [None, None])[1]
    return {'type': 'invoice', 'number': number, 'date': date, 'amount': num(total),
            'plan': (plan or '').strip(), 'period': period, 'account': acct}


def parse_receipt(txt):
    m = re.search(r'(\d{4}-\d{2}-\d{4,6})\s+(\d{2}/\d{2}/\d{4})\s+Ksh\s*([\d,\.]+)', txt)
    number = m.group(1) if m else (re.search(r'(\d{4}-\d{2}-\d{4,6})', txt) or [None, None])[1]
    date = m.group(2) if m else None
    total = (re.search(r'Total:\s*Ksh\s*([\d,\.]+)', txt, re.I) or [None, None])[1]
    if m and not total:
        total = m.group(3)
    return {'type': 'receipt', 'number': number, 'date': date, 'amount': num(total)}


def main():
    d = sys.argv[1]
    out_path = sys.argv[2] if len(sys.argv) > 2 else 'database/seeders/data/azanet_bills.json'
    rows = []
    for fn in os.listdir(d):
        if not fn.lower().endswith('.eml'):
            continue
        low = fn.lower()
        if 'invoice for royal kings' not in low and 'payment for royal kings' not in low:
            continue
        pfn, data = first_pdf(os.path.join(d, fn))
        if not data:
            continue
        with pdfplumber.open(io.BytesIO(data)) as pdf:
            txt = pdf.pages[0].extract_text() or ''
        is_receipt = 'PAYMENT' in txt[:40].upper() or low.startswith('payment')
        rec = parse_receipt(txt) if is_receipt else parse_invoice(txt)
        rec['source_file'] = fn
        rec['pdf'] = pfn
        rows.append(rec)

    invoices = [r for r in rows if r['type'] == 'invoice']
    receipts = [r for r in rows if r['type'] == 'receipt']
    # de-dup by number
    seen = set(); inv_u = []
    for r in sorted(invoices, key=lambda x: x['date'] or ''):
        if r['number'] in seen:
            continue
        seen.add(r['number']); inv_u.append(r)
    seen = set(); rcp_u = []
    for r in sorted(receipts, key=lambda x: x['date'] or ''):
        if r['number'] in seen:
            continue
        seen.add(r['number']); rcp_u.append(r)

    print(f'Invoices: {len(inv_u)}  total Ksh {sum(r["amount"] for r in inv_u):,.0f}')
    print(f'Receipts: {len(rcp_u)}  total Ksh {sum(r["amount"] for r in rcp_u):,.0f}')
    print('\n-- INVOICES --')
    for r in inv_u:
        print(f"  {r['number']:14s} {r['date']}  Ksh {r['amount']:>8,.0f}  {r['plan']}  {r['period']}")
    print('\n-- RECEIPTS --')
    for r in rcp_u:
        print(f"  {str(r['number']):16s} {str(r['date'])}  Ksh {r['amount']:>8,.0f}")

    os.makedirs(os.path.dirname(out_path), exist_ok=True)
    with open(out_path, 'w', encoding='utf-8') as f:
        json.dump({'vendor': 'Azanet Solutions Ltd', 'invoices': inv_u, 'receipts': rcp_u}, f, indent=1)
    print('\nWrote', out_path)


if __name__ == '__main__':
    main()
