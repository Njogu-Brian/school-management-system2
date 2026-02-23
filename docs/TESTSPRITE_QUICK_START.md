# TestSprite Quick Start Checklist

## ✅ Pre-Flight Checklist

### 1. Prerequisites Verification
- [ ] Node.js >= 22 installed (`node --version`)
- [ ] TestSprite account created (https://testsprite.com)
- [ ] API key obtained from TestSprite dashboard
- [ ] Cursor IDE installed and running

### 2. Application Setup
- [ ] Laravel application dependencies installed (`composer install`)
- [ ] Frontend dependencies installed (`npm install` in frontend directory)
- [ ] `.env` file configured with database credentials
- [ ] Database migrated (`php artisan migrate`)
- [ ] Database seeded with test data (`php artisan db:seed`)
- [ ] Application starts successfully (`php artisan serve`)

### 3. TestSprite MCP Configuration
- [ ] API key copied from TestSprite dashboard
- [ ] MCP server added to Cursor settings
- [ ] "Run in Sandbox" mode **DISABLED** in Cursor
- [ ] Cursor restarted after MCP configuration
- [ ] TestSprite MCP connection verified

### 4. Test Data Preparation
- [ ] Test accounts created in database:
  - [ ] Admin account
  - [ ] Teacher account
  - [ ] Parent account
  - [ ] Student account
  - [ ] Finance account
- [ ] Test data seeded (students, classes, fee structures, etc.)
- [ ] Test credentials documented in `TESTSPRITE_TEST_CONFIG.json`

### 5. Application Running
- [ ] Backend server running on http://localhost:8000
- [ ] Frontend accessible (if applicable)
- [ ] Can access login page
- [ ] Can log in with test accounts

## 🚀 Quick Start Steps

### Step 1: Get API Key (5 minutes)
1. Go to https://testsprite.com
2. Sign up or log in
3. Navigate to **Settings** → **API Keys**
4. Click **"New API Key"**
5. Copy the key

### Step 2: Configure MCP in Cursor (5 minutes)
1. Open Cursor Settings (Ctrl+,)
2. Search for "MCP" or navigate to MCP settings
3. Add TestSprite MCP server with your API key
4. **IMPORTANT:** Disable "Run in Sandbox" mode
5. Restart Cursor

### Step 3: Start Application (2 minutes)
```bash
# In project root directory
php artisan serve
# Or use the dev script
composer run dev
```

### Step 4: Verify Setup (2 minutes)
1. Open Cursor chat
2. Type: "Can you test this project with TestSprite?"
3. If TestSprite responds, setup is complete!

### Step 5: Run First Test (5 minutes)
Ask TestSprite to:
- Test the login functionality
- Test a simple CRUD operation
- Verify API endpoints

## 📋 Test Configuration Update

Before running comprehensive tests:

1. **Update `TESTSPRITE_TEST_CONFIG.json`:**
   - Replace placeholder credentials with actual test account credentials
   - Verify application URLs match your setup
   - Review test scenarios and adjust priorities

2. **Configure in TestSprite Dashboard:**
   - Add your application URLs
   - Upload test credentials
   - Select testing types (Backend/Frontend/Codebase)

## 🎯 Recommended First Tests

Start with these critical paths:

1. **Authentication Flow**
   ```
   "Test the login and logout functionality"
   ```

2. **Student Management**
   ```
   "Test creating a new student and viewing their profile"
   ```

3. **Payment Processing**
   ```
   "Test the complete payment flow from invoice to receipt"
   ```

4. **Bank Reconciliation**
   ```
   "Test importing a bank statement and reconciling payments"
   ```

## 🔍 Verification Commands

### Check Application Status
```bash
# Check if Laravel is running
curl http://localhost:8000

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Check TestSprite MCP
In Cursor chat, try:
```
"List available TestSprite commands"
"Help me test the login page"
```

## ⚠️ Common Issues

### Issue: TestSprite MCP not responding
**Solution:**
- Verify API key is correct
- Check "Run in Sandbox" is disabled
- Restart Cursor
- Check MCP server logs

### Issue: Tests can't connect to application
**Solution:**
- Verify application is running
- Check URL in test configuration
- Verify firewall isn't blocking connections
- Check application logs for errors

### Issue: Authentication tests failing
**Solution:**
- Verify test account credentials in database
- Check if accounts are active
- Verify role permissions are set correctly
- Check session configuration

## 📚 Next Steps

After initial setup:
1. ✅ Run smoke tests on all major modules
2. ✅ Test critical business flows
3. ✅ Review test reports
4. ✅ Fix identified issues
5. ✅ Set up continuous testing

## 📞 Need Help?

- **TestSprite Docs:** https://docs.testsprite.com
- **Setup Guide:** See `TESTSPRITE_SETUP_GUIDE.md`
- **Test Config:** See `TESTSPRITE_TEST_CONFIG.json`
- **System Docs:** See `SYSTEM_DOCUMENTATION.md`

---

**Ready to test?** Start your application and ask TestSprite: *"Can you test this project with TestSprite?"*
