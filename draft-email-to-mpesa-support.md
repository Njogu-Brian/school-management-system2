Subject: Production STK Push Authentication Error - Invalid Access Token (404.001.03)

Dear M-PESA API Support Team,

I am writing to report an issue with our production M-PESA Daraja API integration. We are experiencing "Invalid Access Token" errors when attempting to initiate STK Push payments, despite using valid credentials and fresh access tokens.

**Business Details:**
- Business Name: Royal Kings Education Centre Ltd
- Production App Name: Prod-royal-kings-education-centre-ltd-1673005213
- Shortcode: 4068473
- Created: Friday, 2nd January 2026

**Technical Details:**
- Consumer Key: FEnT6oUkePJUsLSqNSi2UsB7rrYx4roX
- Consumer Secret: f1OBq7WCgEpPTGtD
- Shortcode: 4068473
- Passkey: cf45ec4ce38fd3b221ea59ee150c3297a7d77d3a0af7bf84e8d2ce0b4a6fe59f
- Environment: Production
- OAuth URL: https://api.safaricom.co.ke/oauth/v1/generate
- STK Push URL: https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest

**Problem Description:**
1. Access tokens are being generated successfully from the OAuth endpoint
2. When we use these tokens to initiate STK Push requests, we receive error code "404.001.03 - Invalid Access Token"
3. We are using fresh tokens for each request (not caching tokens)
4. The error occurs immediately after token generation, suggesting the token itself is invalid or not accepted

**What We've Verified:**
- ✅ Consumer Key and Consumer Secret are correct (confirmed from Daraja portal)
- ✅ Shortcode (4068473) matches our production app
- ✅ Passkey matches the one provided by API Support
- ✅ Environment is set to "production"
- ✅ Using correct production API endpoints (api.safaricom.co.ke)
- ✅ Fresh access tokens are obtained for each request
- ✅ Phone numbers are correctly formatted (254XXXXXXXXX)

**Error Details:**
- Error Code: 404.001.03
- Error Message: "Invalid Access Token"
- HTTP Status: 404
- Occurs on: STK Push endpoint (/mpesa/stkpush/v1/processrequest)

**Request for Assistance:**
We kindly request your help to resolve this issue. Please verify:

1. **IP Whitelisting**: Is our server IP address whitelisted for production access?
   - Our server domain: erp.royalkingsschools.sc.ke
   - Our server IP address: 105.160.4.60
   - Please confirm if this IP is whitelisted or if we need to add it for production access

2. **App Approval Status**: Is our production app fully approved and activated for STK Push transactions?

3. **Token Validation**: Could you verify if there are any additional requirements or restrictions on access token usage that we might be missing?

4. **Credentials Verification**: Please confirm that the Consumer Key, Consumer Secret, Shortcode, and Passkey provided above are correctly configured and active in your system.

**Sample Request Details:**
- Transaction Amount: KES 10.00 (test transaction)
- Phone Number: 254723014032 (valid Kenyan Safaricom number)
- Transaction Type: CustomerPayBillOnline
- Business Shortcode: 4068473

**Contact Information:**
- Business: Royal Kings Education Centre Ltd
- Email: [Your contact email]
- Phone: [Your contact phone]
- Website: erp.royalkingsschools.sc.ke

We would appreciate your prompt assistance in resolving this issue so we can continue processing payments for our students and parents.

Thank you for your support.

Best regards,
[Your Name]
[Your Title]
Royal Kings Education Centre Ltd

---

**Technical Support Team Notes:**
If you need additional information or logs, please let us know. We can provide:
- Full HTTP request/response logs
- Server IP addresses for whitelisting
- Additional test transaction details
