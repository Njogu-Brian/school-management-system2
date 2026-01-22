# TestSprite Setup and Testing Guide

## Overview
This guide will help you set up and use TestSprite to test your School Management System.

## Prerequisites
- Node.js >= 22 installed
- TestSprite account (sign up at https://testsprite.com)
- Laravel application running locally
- Cursor IDE (or compatible IDE)

## Step 1: Get Your TestSprite API Key

1. Sign in to [TestSprite Dashboard](https://testsprite.com)
2. Navigate to **Settings** → **API Keys**
3. Click **"New API Key"**
4. Copy the API key (you'll need it for MCP configuration)

## Step 2: Install TestSprite MCP Server in Cursor

### Option A: One-Click Installation (Recommended)
1. Open Cursor Settings
2. Search for "MCP" or "TestSprite"
3. Follow the one-click installation if available

### Option B: Manual Configuration
1. Open Cursor Settings (Ctrl+,)
2. Navigate to **Features** → **MCP Servers** (or search for "MCP")
3. Add a new MCP server configuration:

```json
{
  "mcpServers": {
    "testsprite": {
      "command": "npx",
      "args": [
        "-y",
        "@testsprite/mcp-server"
      ],
      "env": {
        "TESTSPRITE_API_KEY": "your-api-key-here"
      }
    }
  }
}
```

4. Replace `your-api-key-here` with your actual API key
5. Restart Cursor

## Step 3: Important Cursor Settings

**Disable Sandbox Mode:**
1. Go to Cursor Settings
2. Search for "sandbox" or "Run in Sandbox"
3. **Disable** "Run in Sandbox" mode
   - This is required for TestSprite to function properly

## Step 4: Prepare Your Application

### Start Your Laravel Application
```bash
# Option 1: Using Laravel's built-in server
php artisan serve

# Option 2: Using the dev script (includes queue, logs, vite)
composer run dev
```

Your application should be accessible at:
- **Backend:** http://localhost:8000 (or your configured port)
- **Frontend:** http://localhost:5173 (if using Vite)

### Create Test Accounts (if needed)
TestSprite may need test account credentials. Prepare:
- Admin account credentials
- Teacher account credentials
- Parent account credentials
- Student account credentials

## Step 5: Configure TestSprite Testing

### Access TestSprite Portal
1. Go to [TestSprite Dashboard](https://testsprite.com)
2. Navigate to **Projects** or **Test Configuration**

### Configure Your Project
1. **Select Testing Type:**
   - ✅ Backend (Laravel API)
   - ✅ Frontend (Blade Templates + React)
   - ✅ Codebase Analysis

2. **Add Application URLs:**
   - Base URL: `http://localhost:8000`
   - Frontend URL: `http://localhost:5173` (if applicable)
   - API Base: `http://localhost:8000/api`

3. **Add Test Credentials:**
   - Admin: `admin@school.com` / `password`
   - Teacher: `teacher@school.com` / `password`
   - Parent: `parent@school.com` / `password`
   - (Update with your actual test credentials)

4. **Upload Product Requirements Document (Optional):**
   - You can upload `SYSTEM_DOCUMENTATION.md` or create a PRD

## Step 6: Run Tests

### Method 1: Through Cursor Chat
Once TestSprite MCP is configured, you can ask:
```
"Can you test this project with TestSprite?"
"Test the login functionality"
"Test the payment processing flow"
"Run end-to-end tests for the finance module"
```

### Method 2: Through TestSprite Dashboard
1. Go to TestSprite Dashboard
2. Click **"Run Tests"** or **"Create Test Plan"**
3. Select test scenarios
4. Execute tests

## Step 7: Review Test Results

TestSprite will:
- ✅ Generate comprehensive test plans
- ✅ Execute tests in the cloud
- ✅ Provide detailed reports
- ✅ Suggest automatic bug fixes
- ✅ Generate test coverage reports

## Testing Focus Areas for This Project

Based on your School Management System, focus on:

### 1. Authentication & Authorization
- Login/logout flows
- Role-based access control
- Password reset
- Session management

### 2. Student Management
- Student registration
- Family management
- Student profiles
- Academic history

### 3. Finance Module
- Fee structure creation
- Payment processing
- Invoice generation
- Payment allocation
- Bank statement reconciliation
- Payment reversals

### 4. Attendance
- Marking attendance
- Attendance reports
- Notification system

### 5. Academics
- Class management
- Subject management
- Timetable
- Exam management

### 6. Communication
- Announcements
- SMS/Email sending
- Notifications

## Troubleshooting

### TestSprite MCP Not Working
- Verify API key is correct
- Check that "Run in Sandbox" is disabled
- Restart Cursor
- Check MCP server logs in Cursor

### Tests Not Running
- Ensure Laravel app is running
- Verify URLs are accessible
- Check test credentials are correct
- Review TestSprite dashboard for errors

### Connection Issues
- Check firewall settings
- Verify localhost URLs are correct
- Ensure no port conflicts

## Next Steps

1. ✅ Complete TestSprite setup
2. ✅ Configure test accounts
3. ✅ Run initial test suite
4. ✅ Review and fix issues
5. ✅ Set up continuous testing

## Support

- TestSprite Documentation: https://docs.testsprite.com
- TestSprite Support: support@testsprite.com
- Laravel Testing Docs: https://laravel.com/docs/testing
