# Message for HostPinnacle Support Team

**Subject:** API Integration Issues - Account Status Endpoint and Empty msgId Response

---

Dear HostPinnacle Support Team,

I am experiencing several issues with the SMS API integration and would appreciate your assistance in resolving them.

## Issue 1: Account Status/Balance Check Endpoint (404 Error)

**Problem:** 
When attempting to check account status/balance using the API, I'm receiving 404 errors.

**What I've tried:**
- Endpoint: `GET https://smsportal.hostpinnacle.co.ke/SMSApi/account/readstatus`
- Parameters: `userid`, `password`, `output=json`
- Method: GET (as per your API documentation)
- Note: I'm NOT including the API key in the GET request, as your documentation states "You cannot use API key in GET Method"

**Questions:**
1. Is `GET /SMSApi/account/readstatus` the correct endpoint for checking account status and balance?
2. What are the exact required parameters for this endpoint?
3. What is the expected response format (JSON structure)? Specifically, what field name contains the account balance?
4. Are there any additional authentication requirements beyond `userid` and `password`?

**Alternative endpoint I've also tried:**
- `POST /SMSApi/account/readcredithistory` - What is the correct usage and response format for this endpoint?

## Issue 2: Empty msgId in Success Response

**Problem:**
When sending SMS, the API returns:
```json
{
  "status": "success",
  "statusCode": "200",
  "reason": "success",
  "msgId": "",  // <-- This is empty
  "transactionId": "4406391537250813595",
  "mobile": "254720573286"
}
```

**Questions:**
1. Why does the API return `status: "success"` with an empty `msgId`?
2. Does an empty `msgId` mean the message was NOT queued for delivery?
3. What are the common causes for this response?
4. Is this related to insufficient account balance, account suspension, or another issue?
5. Should I check balance before sending, or is there a minimum balance requirement?

## Issue 3: Response Format Clarification

**Questions:**
1. For the account status endpoint, what is the exact JSON response structure?
2. What field name contains the current SMS balance/credits?
3. Are there any error codes I should be aware of?

## Current Configuration

- **Username:** royalce1
- **API Method:** Using POST for sending SMS (with API key)
- **Webhook:** Configured for DLR reports at `https://erp.royalkingsschools.sc.ke/webhooks/sms/dlr`
- **SMS Sending:** Working (returns success), but msgId is empty

## What I Need

1. **Correct endpoint URL** for checking account balance/status
2. **Exact request format** (GET vs POST, required parameters, headers)
3. **Response format documentation** with example JSON responses
4. **Explanation of empty msgId** issue and how to resolve it
5. **Any API documentation** or sample code for balance checking

## Additional Information

I have reviewed your API documentation and Swagger interface. I can see:
- Account Status endpoint listed as: `GET /SMSApi/account/readstatus`
- Credit History endpoint listed as: `POST /SMSApi/account/readcredithistory`
- Note about API key not being usable in GET method

However, I'm still receiving 404 errors when attempting to use the account status endpoint, which suggests either:
- The endpoint path is incorrect
- Additional parameters or authentication are required
- The endpoint requires a different format

I would greatly appreciate your assistance in resolving these issues so I can properly integrate the balance checking functionality and understand why messages are not being queued (empty msgId).

Thank you for your time and assistance.

Best regards,
[Your Name]
[Your Contact Information]

---

**Attachments/References:**
- Screenshot of webhook configuration showing messageId parameter setup
- Screenshot of Build SMS API page showing "Read Credit History" option
- Error logs showing 404 responses and empty msgId responses
