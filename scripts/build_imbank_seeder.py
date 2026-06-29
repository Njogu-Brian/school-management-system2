"""
Parse an I&M Bank PDF statement + a phone SMS backup, match each WITHDRAWAL to
the matching I&M / M-PESA SMS to recover the M-PESA reference and recipient
name, then emit database/seeders/data/imbank_transactions.json that
ImBankStatementSeeder.php loads into the expense statement analyzer.

Usage:
  python scripts/build_imbank_seeder.py "<statement.pdf>" <pdf_password> "<sms.xml>"
"""
import sys, os, re, json, hashlib
from datetime import datetime, timedelta
from collections import defaultdict
import xml.etree.ElementTree as ET
import pdfplumber

DATE_RE = re.compile(r'^\d{2}-\d{2}-\d{2}$')


def to_amount(v):
    if not v:
        return 0.0
    s = str(v).replace(',', '').replace('Cr', '').replace('Dr', '').strip()
    try:
        return round(float(s), 2)
    except ValueError:
        return 0.0


def stmt_date_to_iso(d):
    # dd-mm-yy -> yyyy-mm-dd
    dd, mm, yy = d.split('-')
    return f"20{yy}-{mm}-{dd}"


def last9(num):
    digits = re.sub(r'\D', '', str(num))
    return digits[-9:] if len(digits) >= 9 else digits


# --------------------------------------------------------------------------
# 1. Parse the PDF -> list of withdrawal transactions
# --------------------------------------------------------------------------
def parse_account(path, password):
    with pdfplumber.open(path, password=password) as pdf:
        t = pdf.pages[0].extract_text() or ''
    name = (re.search(r'Account Name\s+(.+?)\s+Page', t) or [None, None])[1]
    number = (re.search(r'Account Number\s+(\S+)', t) or [None, None])[1]
    period = re.search(r'Statement Period\s+(\d{2}-\d{2}-\d{4})\s+To\s+(\d{2}-\d{2}-\d{4})', t)
    return {
        'account_name': (name or '').strip() or None,
        'account_number': (number or '').strip() or None,
    }


def load_phone_name_map(data_dir, exclude=None):
    """Build phone(last9) -> name from previously generated I&M data files."""
    import glob
    from collections import Counter as _C
    votes = {}
    for fp in glob.glob(os.path.join(data_dir, 'imbank_transactions*.json')):
        if exclude and os.path.abspath(fp) == os.path.abspath(exclude):
            continue
        try:
            data = json.load(open(fp, encoding='utf-8'))
        except Exception:
            continue
        for ln in data.get('lines', []):
            ph = ln.get('recipient_phone')
            nm = (ln.get('vendor_name') or ln.get('recipient_name') or '').strip()
            if ph and nm and not nm.isdigit():
                votes.setdefault(last9(ph), _C())[nm] += 1
    return {p: c.most_common(1)[0][0] for p, c in votes.items()}


def parse_pdf(path, password):
    txns = []
    with pdfplumber.open(path, password=password) as pdf:
        for page in pdf.pages:
            for table in page.extract_tables():
                if not table:
                    continue
                header = [(c or '').strip() for c in table[0]]
                if 'Tran Date' not in header:
                    continue
                cur = None
                for row in table[1:]:
                    cells = list(row) + [None] * (9 - len(row))
                    tran = (cells[0] or '').strip()
                    narr = (cells[7] or '').strip()
                    if DATE_RE.match(tran):
                        # finalize previous
                        if cur:
                            txns.append(cur)
                        cur = {
                            'date': stmt_date_to_iso(tran),
                            'withdrawn': to_amount(cells[3]),
                            'paid_in': to_amount(cells[4]),
                            'balance': (cells[5] or '').strip(),
                            'narr_parts': [narr] if narr else [],
                        }
                    elif cur is not None and narr:
                        cur['narr_parts'].append(narr)
                if cur:
                    txns.append(cur)
    # keep only withdrawals
    out = []
    for t in txns:
        if t['withdrawn'] <= 0:
            continue
        t['narration'] = ' '.join(t['narr_parts']).strip()
        del t['narr_parts']
        out.append(t)
    return out


# --------------------------------------------------------------------------
# 2. Parse the SMS backup -> indexes for matching
# --------------------------------------------------------------------------
def parse_sms(path):
    transfers = []   # bank->mpesa: phone, amount, name, mpesa_ref, dt
    purchases = []   # card purchases: amount, merchant, dt
    paid_to = []     # till/paybill paid: amount, name, ref, dt
    payments = []    # generic "Payment of KES x to <phone>": phone, amount, ref, dt

    if not path or path in ('-', 'none', 'None'):
        return transfers, purchases, paid_to, payments

    re_transfer = re.compile(
        r'Bank to M-PESA transfer of KES\s*([\d,]+\.?\d*)\s*to\s*(\d+)\s*-\s*(.+?)\s+successfully processed\..*?M-PESA Ref ID:\s*([A-Z0-9]+)',
        re.I)
    re_purchase = re.compile(
        r'made a purchase of\s*KES\s*([\d,]+\.?\d*)\s*on\s*([\d\-: ]+?)\s*at\s*(.+?)\s*using I&M', re.I)
    re_paid = re.compile(
        r'KES\s*([\d,]+\.?\d*)\s*paid to\s*(.+?)\s*\(Acc\s*([^\)]*)\).*?Ref:\s*([A-Z0-9]+)', re.I)
    re_payment = re.compile(
        r'Payment of KES\s*([\d,]+\.?\d*)\s*to\s*(\d+)\s*on.*?Transaction Ref ID:\s*([A-Z0-9]+)', re.I | re.S)

    for ev, el in ET.iterparse(path, events=('end',)):
        if el.tag != 'sms':
            el.clear(); continue
        addr = el.get('address') or ''
        body = el.get('body') or ''
        epoch = int(el.get('date') or 0) / 1000.0
        dt = datetime.fromtimestamp(epoch) if epoch else None

        m = re_transfer.search(body)
        if m:
            transfers.append({
                'amount': to_amount(m.group(1)),
                'phone9': last9(m.group(2)),
                'name': re.sub(r'\s+', ' ', m.group(3)).strip(),
                'ref': m.group(4).strip(),
                'dt': dt,
            })
            el.clear(); continue

        m = re_purchase.search(body)
        if m:
            try:
                pdt = datetime.strptime(m.group(2).strip(), '%Y-%m-%d %H:%M:%S')
            except ValueError:
                pdt = dt
            purchases.append({
                'amount': to_amount(m.group(1)),
                'merchant': re.sub(r'\s+', ' ', m.group(3)).strip(),
                'dt': pdt,
            })
            el.clear(); continue

        m = re_paid.search(body)
        if m:
            paid_to.append({
                'amount': to_amount(m.group(1)),
                'name': re.sub(r'\s+', ' ', m.group(2)).strip(),
                'acc': m.group(3).strip(),
                'ref': m.group(4).strip(),
                'dt': dt,
            })
            el.clear(); continue

        m = re_payment.search(body)
        if m:
            payments.append({
                'amount': to_amount(m.group(1)),
                'phone9': last9(m.group(2)),
                'ref': m.group(3).strip(),
                'dt': dt,
            })
        el.clear()

    return transfers, purchases, paid_to, payments


def title_case(s):
    s = (s or '').strip()
    return s.title() if s else s


def group_key(*parts):
    norm = [re.sub(r'\s+', '', str(p).lower()) for p in parts if p]
    return hashlib.sha1('|'.join(norm).encode()).hexdigest()[:32]


def fingerprint(receipt, completed, narration, salt):
    return hashlib.sha256('|'.join([str(receipt or ''), completed or '', (narration or '').strip(), str(salt)]).encode()).hexdigest()


# --------------------------------------------------------------------------
# 3. Match + build line records
# --------------------------------------------------------------------------
def build(pdf_path, password, sms_path, phone_names=None):
    phone_names = phone_names or {}
    txns = parse_pdf(pdf_path, password)
    transfers, purchases, paid_to, payments = parse_sms(sms_path)

    # bucket SMS by matching keys, keep used flags
    t_by_key = defaultdict(list)
    for s in transfers:
        t_by_key[(s['phone9'], s['amount'])].append(s)
    for lst in t_by_key.values():
        lst.sort(key=lambda x: x['dt'] or datetime.min)

    pur_by_amt = defaultdict(list)
    for s in purchases:
        pur_by_amt[s['amount']].append(s)
    for lst in pur_by_amt.values():
        lst.sort(key=lambda x: x['dt'] or datetime.min)

    paid_by_amt = defaultdict(list)
    for s in paid_to:
        paid_by_amt[s['amount']].append(s)
    for lst in paid_by_amt.values():
        lst.sort(key=lambda x: x['dt'] or datetime.min)

    pay_by_key = defaultdict(list)
    for s in payments:
        pay_by_key[(s['phone9'], s['amount'])].append(s)
    for lst in pay_by_key.values():
        lst.sort(key=lambda x: x['dt'] or datetime.min)

    used = set()  # ids of consumed sms (by object id)

    def pick_nearest(candidates, target_date, max_days=3):
        best, best_gap = None, None
        for c in candidates:
            if id(c) in used or not c['dt']:
                continue
            gap = abs((c['dt'].date() - target_date).days)
            if gap > max_days:
                continue
            if best is None or gap < best_gap:
                best, best_gap = c, gap
        if best:
            used.add(id(best))
        return best

    re_phone_prefixed = re.compile(r'^(\d{9,12})/(.*)$', re.S)
    re_plain_mpesa = re.compile(r'^MPESA Payment\s*to\s*\d+', re.I)
    re_ecitizen = re.compile(r'^([A-Z0-9]{6,12})-([A-Za-z].+)$')
    fee_markers = ('FACILITY FEE', 'EXCISE DUTY', 'CREDIT LIFE', 'CHARGE', 'LEDGER FEE',
                   'COMMISSION', 'SERVICE FEE', 'LOAN APPRAISAL')

    lines = []
    stats = defaultdict(int)

    for i, t in enumerate(txns):
        narr = t['narration']
        upper = narr.upper()
        tdate = datetime.strptime(t['date'], '%Y-%m-%d').date()
        amount = t['withdrawn']

        receipt = None
        vendor = None
        recipient_name = None
        recipient_phone = None
        description = None
        ttype = 'other'
        is_fee = False
        gkey = None
        completed = t['date'] + ' 12:00:00'

        m_phone = re_phone_prefixed.match(narr.strip())
        if m_phone:
            phone = m_phone.group(1)
            note = re.sub(r'\s+', ' ', m_phone.group(2)).strip()
            recipient_phone = phone if phone.startswith('254') else ('254' + phone.lstrip('0'))
            ttype = 'send_money'
            # A user-typed note (anything other than the boilerplate "MPESA Payment to <phone>")
            # is the actual expense purpose -> keep it as the description.
            if note and not re_plain_mpesa.match(note):
                description = note
            p9 = last9(phone)
            sms = pick_nearest(t_by_key.get((p9, amount), []), tdate)
            if sms:
                receipt = sms['ref']
                vendor = title_case(sms['name'])
                recipient_name = vendor
                if sms['dt']:
                    completed = sms['dt'].strftime('%Y-%m-%d %H:%M:%S')
                stats['matched_transfer'] += 1
            else:
                sms = pick_nearest(pay_by_key.get((p9, amount), []), tdate)
                if sms:
                    receipt = sms['ref']
                    if sms['dt']:
                        completed = sms['dt'].strftime('%Y-%m-%d %H:%M:%S')
                    stats['matched_payment'] += 1
                else:
                    stats['unmatched_mpesa'] += 1
            if not recipient_name:
                recipient_name = description or recipient_phone
            gkey = group_key('send_money', recipient_phone)

        elif any(k in upper for k in fee_markers):
            ttype = 'fee'
            is_fee = True
            recipient_name = 'I&M Bank Charges'
            vendor = None
            gkey = 'fee:general'
            stats['fee'] += 1

        elif 'SHOWMAX' in upper or re.search(r'PRCR\d', narr) or 'OPENAI' in upper:
            ttype = 'buy_goods'
            description = 'Card purchase'
            sms = pick_nearest(pur_by_amt.get(amount, []), tdate, max_days=2)
            if sms:
                vendor = title_case(sms['merchant'])
                stats['matched_card'] += 1
            else:
                merchant = re.sub(r'\s*\d{3,4}\s+\d{4,6}\s*PRCR\d+\s*$', '', narr, flags=re.I).strip()
                vendor = title_case(merchant) or narr[:60]
                stats['unmatched_card'] += 1
            recipient_name = vendor
            gkey = group_key('buy_goods', vendor or narr)

        elif re.match(r'^Airtel Money to (\d{9,12})', narr, re.I):
            mm = re.match(r'^Airtel Money to (\d{9,12})', narr, re.I)
            phone = mm.group(1)
            recipient_phone = phone if phone.startswith('254') else ('254' + phone.lstrip('0'))
            ttype = 'send_money'
            description = 'Airtel Money transfer'
            recipient_name = recipient_phone
            gkey = group_key('send_money', recipient_phone)
            stats['airtel'] += 1

        elif re_ecitizen.match(narr.strip()):
            m_ec = re_ecitizen.match(narr.strip())
            extra = re.sub(r'^ECITIZEN', '', m_ec.group(2), flags=re.I).strip()
            ttype = 'paybill'
            receipt = m_ec.group(1).upper()
            vendor = 'eCitizen'
            recipient_name = vendor
            description = ('eCitizen - ' + title_case(extra)) if extra else 'eCitizen / government payment'
            gkey = group_key('paybill', 'ecitizen')
            stats['ecitizen'] += 1

        else:
            # try paybill / till "paid to" match by amount+date
            sms = pick_nearest(paid_by_amt.get(amount, []), tdate, max_days=2)
            if sms:
                ttype = 'paybill'
                receipt = sms['ref']
                vendor = title_case(sms['name'])
                recipient_name = vendor
                if sms['dt']:
                    completed = sms['dt'].strftime('%Y-%m-%d %H:%M:%S')
                gkey = group_key('paybill', sms['acc'] or sms['name'])
                stats['matched_paid'] += 1
            else:
                # Decode the remaining cryptic I&M narrations into a readable purpose.
                m_purpose = re.match(r'^\d+/(.+)$', narr)
                if m_purpose:
                    pu = m_purpose.group(1).strip().upper()
                    if 'SALARY' in pu:
                        description = 'Salary payment'
                    elif 'LOAN' in pu:
                        description = 'Loan repayment'
                    elif 'UTILIT' in pu:
                        description = 'Utilities'
                    elif 'TITHE' in pu:
                        description = 'Tithe'
                    elif not pu.startswith('MPESA PAYMENT'):
                        description = title_case(m_purpose.group(1).strip())
                elif 'DEBIT FROM PAYOFF SOURCE' in upper or 'LOAN RECOVERY' in upper:
                    description = 'Loan repayment'
                elif upper.startswith('PAYMENT TO '):
                    description = 'Account transfer'
                elif re.match(r'^\d{6,}$', narr.strip()):
                    description = 'Paybill / account payment'
                    ttype = 'paybill'
                recipient_name = title_case(re.sub(r'\s+', ' ', narr)[:80])
                gkey = group_key('other', narr)
                stats['other'] += 1

        # No-SMS fallback: label send-money recipients by phone from known I&M data.
        if not vendor and recipient_phone:
            known = phone_names.get(last9(recipient_phone))
            if known:
                vendor = known
                if not recipient_name or recipient_name == recipient_phone:
                    recipient_name = known
                stats['matched_known_phone'] += 1

        fp = fingerprint(receipt, completed, narr, f"{i}|{t['balance']}")

        lines.append({
            'receipt_no': (receipt or '')[:32] or None,
            'completed_at': completed,
            'narration': narr,
            'line_fingerprint': fp,
            'withdrawn_amount': amount,
            'paid_in_amount': 0,
            'direction': 'out',
            'transaction_type': ttype,
            'is_transaction_fee': is_fee,
            'recipient_name': recipient_name,
            'vendor_name': vendor,
            'recipient_phone': recipient_phone,
            'paybill_number': None,
            'account_reference': None,
            'merchant_reference': None,
            'group_key': gkey,
            'expense_description': description,
            'tran_date': t['date'],
            'balance': t['balance'],
        })

    return lines, stats, txns


def main():
    # Usage: build_imbank_seeder.py <pdf> <password> [sms_path|-] [out.json]
    pdf_path, password = sys.argv[1], sys.argv[2]
    sms_path = sys.argv[3] if len(sys.argv) > 3 else '-'

    out_dir = os.path.join('database', 'seeders', 'data')
    os.makedirs(out_dir, exist_ok=True)
    out_path = sys.argv[4] if len(sys.argv) > 4 else os.path.join(out_dir, 'imbank_transactions.json')

    acct = parse_account(pdf_path, password)
    phone_names = load_phone_name_map(out_dir, exclude=out_path)

    lines, stats, txns = build(pdf_path, password, sms_path, phone_names)

    total_amt = round(sum(l['withdrawn_amount'] for l in lines), 2)
    dates = [l['tran_date'] for l in lines]
    payload = {
        'source': 'bank',
        'bank': 'I&M Bank',
        'original_filename': os.path.basename(pdf_path),
        'account_name': acct['account_name'] or 'I&M Account',
        'account_number': acct['account_number'],
        'period_start': min(dates) if dates else None,
        'period_end': max(dates) if dates else None,
        'outgoing_count': len(lines),
        'outgoing_total': total_amt,
        'lines': lines,
    }

    with open(out_path, 'w', encoding='utf-8') as f:
        json.dump(payload, f, ensure_ascii=False, indent=1)

    print(f"Account            : {payload['account_name']}  {payload['account_number']}")
    print(f"Known phones loaded : {len(phone_names)}")
    print(f"Withdrawals parsed : {len(lines)}")
    print(f"Total withdrawn    : KES {total_amt:,.2f}")
    print(f"Period             : {payload['period_start']} -> {payload['period_end']}")
    print("Match breakdown    :")
    for k in sorted(stats):
        print(f"   {k:18s}: {stats[k]}")
    matched_ref = sum(1 for l in lines if l['receipt_no'])
    named = sum(1 for l in lines if l['vendor_name'])
    print(f"With M-Pesa ref    : {matched_ref}")
    print(f"With vendor name   : {named}")
    print(f"Wrote {out_path}")


if __name__ == '__main__':
    main()
