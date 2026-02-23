# HostPinnacle Support Ticket - Empty msgId Issue

**Subject:** SMS API Returns Success with Empty msgId Despite Sufficient Account Balance

---

Dear HostPinnacle Support Team,

I am experiencing a critical issue with the SMS API where messages are being accepted (status: success) but are not being queued for delivery (empty msgId). This is happening despite having sufficient account balance.

## Issue Summary

**Problem:** 
When sending SMS via the API, I receive a success response but the `msgId` field is empty, indicating the message was not queued for delivery.

**Current Account Status:**
- Account Balance: 2,823-2,824 SMS credits (verified via account status API)
- Account Status: Active
- API Credentials: Valid and working

## API Response Details

**Send SMS Response:**
```json
{
  "status": "success",
  "statusCode": "200",
  "reason": "success",
  "msgId": "",  // <-- EMPTY - Message not queued
  "transactionId": "490660668357758141",
  "mobile": "254708225397",
  "requestTime": "2026-01-20 13:47:42"
}
```

**Account Status Response (Working Correctly):**
```json
{
  "response": {
    "api": "account",
    "action": "readstatus",
    "status": "success",
    "msg": "success",
    "code": "200",
    "count": 3,
    "account": {
      "endHour": "-1",
      "startHour": "-1",
      "smsBalance": "2823"
    }
  }
}
```

## Questions

1. **Why does the API return `status: "success"` with an empty `msgId`?**
   - Does this mean the message was accepted but NOT queued for delivery?
   - What are the common causes for this response?

2. **Is this related to account balance?**
   - My account shows 2,823 credits, which should be sufficient
   - Is there a minimum balance threshold that must be maintained?
   - Are there any account-level restrictions that could cause this?

3. **Is this an account configuration issue?**
   - Are there any account settings that need to be enabled?
   - Do I need to verify or activate my sender ID?
   - Are there any account limitations or restrictions?

4. **Is this an API issue?**
   - Could this be a bug in the API?
   - Are there known issues with empty msgId responses?
   - Has there been any recent API changes that might affect this?

5. **What should I do to resolve this?**
   - Do I need to contact support to enable something on my account?
   - Are there specific parameters I should include in the send request?
   - Is there a different API endpoint I should use?

## Technical Details

**API Endpoint Used:**
- `POST https://smsportal.hostpinnacle.co.ke/SMSApi/send`

**Request Parameters:**
- `userid`: [Your User ID]
- `password`: [Your Password]
- `mobile`: 254708225397
- `msg`: [Message content]
- `senderid`: [Your Sender ID]
- `msgType`: text/unicode (auto-detected)
- `output`: json
- `sendMethod`: quick
- `duplicatecheck`: false

**Headers:**
- `apikey`: [Your API Key]
- `content-type`: application/x-www-form-urlencoded

**Response:**
- HTTP Status: 200
- Status: success
- StatusCode: 200
- Reason: success
- **msgId: "" (EMPTY)**

## Impact

This issue is preventing SMS messages from being delivered to recipients, which is critical for our operations. Messages appear to be accepted by the API but are not actually being sent.

## What I've Verified

✅ Account balance is sufficient (2,823+ credits)
✅ Account status API is working correctly
✅ API credentials are valid
✅ Sender ID is configured
✅ Request format matches API documentation
✅ HTTP response is 200 (success)

## Requested Actions

1. Please investigate why `msgId` is empty despite success response
2. Verify if there are any account-level restrictions or settings that need adjustment
3. Confirm if this is a known issue and if there's a workaround
4. Provide guidance on how to ensure messages are properly queued for delivery

## Additional Information

- **Account Username:** royalce1
- **Webhook Configured:** Yes (for DLR reports)
- **Sender ID:** Configured and active
- **API Integration:** Using POST method with API key authentication

I would greatly appreciate your urgent assistance in resolving this issue, as it is affecting our ability to send critical SMS notifications.

Thank you for your time and support.

Best regards,
[Your Name]
[Your Contact Information]
[Your Account: royalce1]

---

**Priority:** High - Messages not being delivered despite API success response
