# M-PESA C2B (Customer to Business) Paybill Transactions Setup

## 🎉 What's New

Your system now captures **ALL M-PESA paybill transactions in real-time**! When parents pay directly using your paybill number (without STK Push), the system automatically:

1. ✅ Receives the transaction from M-PESA
2. ✅ Identifies the student using smart AI-like matching
3. ✅ Shows it on your dashboard with real-time updates
4. ✅ Detects and prevents duplicates
5. ✅ Allows you to allocate payments to invoices

---

## 🚀 Quick Start

### Access the C2B Dashboard

1. Go to **Finance → M-PESA Dashboard**
2. Click **"Paybill Transactions (Real-time)"** button (green)
3. You'll see all unallocated transactions with match suggestions

### Dashboard Features

- **Real-time Updates**: Auto-refreshes every 10 seconds
- **Smart Matching**: AI-like algorithm identifies students from transaction data
- **Live Stats**: Today's transactions, pending allocation, auto-matched, duplicates
- **Sound Notifications**: Hear a beep when new transactions arrive
- **Visual Highlights**: New transactions flash green

---

## 📋 How to Allocate a Transaction

1. **Go to C2B Dashboard**: Navigate to the paybill transactions page
2. **Review Transaction**: See payer name, phone, amount, reference
3. **Check Match Suggestions**: System shows possible student matches with confidence %
4. **Click "Allocate"**: Opens allocation screen
5. **Select Student**: 
   - Click a suggestion (one-click)
   - Or use the live search
6. **Allocate to Invoices**: System auto-fills invoices in order of due date
7. **Adjust Amounts**: Change allocation per invoice if needed
8. **Complete**: Click "Complete Allocation" - receipt is auto-generated!

---

## 🧠 Smart Matching Algorithm

The system uses **4 matching strategies** to identify students:

| Method | Confidence | Example |
|--------|------------|---------|
| **Admission Number** | 100% | Reference: "ADM12345" → Matches student with admission 12345 |
| **Invoice Number** | 95% | Reference: "INV-2025-001" → Matches invoice's student |
| **Phone Number** | 75% | Phone matches father/mother/primary contact |
| **Name Similarity** | 60-70% | "JOHN KAMAU" matches student "John Kamau" |

**Auto-Allocation**: If confidence ≥ 80%, student is auto-matched (you still need to allocate to invoices)

---

## 🔄 Setup M-PESA Webhook (IMPORTANT!)

For real-time transactions, you must register your callback URL with M-PESA:

### Step 1: Get Your Callback URL

Your webhook URL is:
```
https://your-domain.com/webhooks/payment/mpesa/c2b
```

**Example**: `https://royalkings.school/webhooks/payment/mpesa/c2b`

### Step 2: Register with Safaricom

**Option A: Via Daraja Portal**
1. Log in to [Daraja Portal](https://developer.safaricom.co.ke)
2. Go to your M-PESA App
3. Navigate to "C2B URLs Registration"
4. Set:
   - **Validation URL**: `https://your-domain.com/webhooks/payment/mpesa/c2b/validation` (optional)
   - **Confirmation URL**: `https://your-domain.com/webhooks/payment/mpesa/c2b`
   - **Response Type**: JSON

**Option B: Via API (Automated)**

Run this in your server terminal:

```bash
curl -X POST 'https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_ACCESS_TOKEN' \
  -d '{
    "ShortCode": "YOUR_PAYBILL_NUMBER",
    "ResponseType": "Completed",
    "ConfirmationURL": "https://your-domain.com/webhooks/payment/mpesa/c2b",
    "ValidationURL": "https://your-domain.com/webhooks/payment/mpesa/c2b/validation"
  }'
```

### Step 3: Test the Webhook

1. Make a small payment to your paybill (e.g., KES 10)
2. Use admission number as reference (e.g., "ADM123")
3. Wait 10-30 seconds
4. Check C2B Dashboard - transaction should appear!

---

## 🔍 Transaction Statuses

| Status | Meaning |
|--------|---------|
| **Unallocated** | New transaction, needs manual allocation |
| **Auto-Matched** | Student identified (≥80% confidence), awaiting invoice allocation |
| **Manually Allocated** | Fully allocated by finance officer |
| **Duplicate** | Same transaction received twice (auto-detected) |

---

## 🎯 Best Practices

### For Parents
Instruct parents to use the reference field wisely:
- **Best**: Admission number (e.g., "ADM12345" or just "12345")
- **Good**: Invoice number (e.g., "INV-2025-001")
- **Okay**: Student name (less accurate)

### For Finance Officers
1. **Check daily**: Review unallocated transactions each morning
2. **Trust high confidence**: 90%+ matches are usually correct
3. **Verify low confidence**: < 70% should be manually verified
4. **Use notes**: Add notes when allocating unusual transactions
5. **Monitor duplicates**: Review duplicate detection accuracy

---

## 📊 Reports & Analytics

### Daily Transaction Report
- **Path**: Finance → M-PESA → C2B Transactions
- **Filters**: Date range, status, search by phone/name
- **Export**: CSV download available

### Key Metrics
- **Today's Transactions**: Total count & amount received today
- **Unallocated Amount**: Money waiting to be assigned
- **Auto-Match Rate**: % of transactions auto-matched
- **Duplicate Rate**: % of duplicate detections

---

## 🛠️ Troubleshooting

### Transactions Not Appearing?

1. **Check webhook registration**: Ensure URL is registered with M-PESA
2. **Verify .env settings**: Confirm `MPESA_SHORTCODE` matches your paybill
3. **Check logs**: `storage/logs/laravel.log` for webhook errors
4. **Test webhook**: Send POST request to `/webhooks/payment/mpesa/c2b`

### Low Match Confidence?

Possible reasons:
- Parent used unclear reference (e.g., "FEES", "SCHOOL")
- Phone number not registered in student/family records
- Name spelling different from records
- **Solution**: Use live search to find correct student

### Duplicate Detections Wrong?

If seeing false duplicates:
- Check if M-PESA is sending callbacks twice (network issue)
- Review duplicate detection logic in `MpesaC2BTransaction::checkForDuplicate()`
- Adjust time window (currently 1 minute) if needed

---

## 🔐 Security Notes

- **Webhook is public**: No authentication required (M-PESA doesn't support it)
- **IP Whitelisting**: Consider restricting to Safaricom IPs
- **Data validation**: All incoming data is validated and sanitized
- **Duplicate prevention**: Built-in to prevent double-processing
- **Audit trail**: All transactions logged with timestamps and user actions

---

## 📱 Mobile-Friendly

The C2B dashboard is fully responsive:
- ✅ Works on phones and tablets
- ✅ Touch-friendly buttons
- ✅ Readable on small screens
- ✅ Quick allocation on-the-go

---

## 🎓 Training Video Ideas

Consider recording these for your team:
1. "How to Allocate a C2B Transaction" (3 min)
2. "Understanding Smart Match Confidence" (2 min)
3. "Handling Low-Confidence Matches" (4 min)
4. "Daily C2B Review Workflow" (5 min)

---

## 📞 Support

For technical issues:
- Check `storage/logs/laravel.log`
- Review webhook payload in `mpesa_c2b_transactions.raw_data`
- Contact M-PESA support for webhook registration issues

---

## 🚀 Future Enhancements

Possible additions:
- [ ] WhatsApp notifications to parents on successful allocation
- [ ] Bulk allocation for multiple transactions
- [ ] Export unallocated transactions to Excel
- [ ] SMS reminders for unclear references
- [ ] Machine learning to improve matching over time

---

## ✅ Phase 2 & 3 Completed

**Phase 2**: Enhanced payment link flow
- Multi-invoice selection with checkboxes
- Select multiple parents to notify
- Real-time total calculation
- Send via SMS, Email, WhatsApp

**Phase 3**: STK Push waiting screen
- Real-time status polling
- Auto-receipt generation
- Payment allocation to invoices
- Cancel transaction option

**C2B System**: Complete paybill transaction management
- Real-time dashboard with auto-refresh
- Smart student matching (4 algorithms)
- Duplicate detection
- Manual allocation UI

---

**All systems are live and ready to use! 🎉**

