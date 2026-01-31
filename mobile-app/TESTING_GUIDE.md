# React Native Mobile App - Testing Guide

## Important: React Native Apps Don't Run in Browsers

React Native apps are **native mobile applications** that run on:
- Android devices or emulators
- iOS devices or simulators (requires macOS)

They **cannot** run in web browsers without additional configuration (React Native Web).

---

## Prerequisites for Testing

### Option 1: Android Emulator (Recommended)

**Requirements:**
1. Android Studio installed
2. Android SDK configured
3. Android Virtual Device (AVD) created

**Setup Steps:**

1. **Install Android Studio**: Download from [developer.android.com](https://developer.android.com/studio)

2. **Install Android SDK**:
   - Open Android Studio → SDK Manager
   - Install: Android SDK Platform 33 (or latest)
   - Install: Android SDK Build-Tools
   - Install: Android Emulator

3. **Create Virtual Device**:
   - Open AVD Manager in Android Studio
   - Click "Create Virtual Device"
   - Select device (e.g., Pixel 5)
   - Select system image (e.g., Android 13 / API 33)
   - Finish and start the emulator

4. **Set Environment Variables** (Windows):
   ```powershell
   # Add to System Environment Variables:
   ANDROID_HOME=C:\Users\YourUsername\AppData\Local\Android\Sdk
   
   # Add to PATH:
   %ANDROID_HOME%\platform-tools
   %ANDROID_HOME%\emulator
   %ANDROID_HOME%\tools
   %ANDROID_HOME%\tools\bin
   ```

---

### Option 2: Physical Android Device

**Requirements:**
1. Android device with USB debugging enabled
2. USB cable

**Setup Steps:**

1. **Enable Developer Options** on your phone:
   - Go to Settings → About Phone
   - Tap "Build Number" 7 times
   - Developer Options will appear in Settings

2. **Enable USB Debugging**:
   - Settings → Developer Options → USB Debugging → Enable

3. **Connect Device**:
   - Connect phone to computer via USB
   - Allow USB debugging when prompted
   - Verify connection: `adb devices`

4. **Update .env** for physical device:
   ```
   # Find your computer's IP address with: ipconfig
   # Replace with your actual IP (e.g., 192.168.1.100)
   API_BASE_URL=http://YOUR_COMPUTER_IP:8000/api
   ```

---

## Running the Mobile App

### Step 1: Start Laravel Backend

Open a terminal in the Laravel project root:

```powershell
cd d:\school-management-system2\school-management-system2
php artisan serve --host=0.0.0.0 --port=8000
```

**Important**: Use `--host=0.0.0.0` to allow connections from emulator/device.

### Step 2: Install Mobile App Dependencies

The npm install is currently running. Once complete:

```powershell
cd mobile-app
npm install
```

### Step 3: Start Metro Bundler

```powershell
npm start
```

Keep this terminal open.

### Step 4: Run on Android

Open a **new terminal** and run:

```powershell
cd mobile-app
npx react-native run-android
```

This will:
- Build the Android app
- Install it on your emulator/device
- Launch the app

---

## Testing the App

### 1. Login Flow

**Test Credentials** (use credentials from your Laravel database):
- Email: admin@example.com (or whatever you created)
- Password: your password

**Expected Behavior:**
1. App opens to login screen
2. Enter credentials
3. Toggle "Remember Me"
4. Tap "Sign In"
5. Navigate to role-based dashboard (Admin shows stats)

### 2. Password Reset (Email)

1. Tap "Forgot Password?"
2. Stay on Email tab
3. Enter email
4. Tap "Send Reset Link"
5. Success message → Laravel sends email
6. Check email for reset link

### 3. Password Reset (OTP)

1. Tap "Forgot Password?"
2. Switch to OTP tab
3. Enter phone number
4. Tap "Send OTP"
5. Navigate to OTP screen
6. Enter 6-digit code
7. Tap "Verify"
8. Enter new password
9. Tap "Reset Password"
10. Redirected to login

### 4. Dashboard

After login:
- See welcome message with your name
- View stats cards (mock data)
- Quick action buttons
- Logout button works

### 5. Teacher & Senior Teacher Flow (Full Functionality)

Log in with a **teacher** or **senior teacher** account. The app normalizes backend role names (e.g. "Teacher", "Senior Teacher") to `teacher` / `senior_teacher`. Both roles use the same **Teacher Navigator**; senior teachers see extra actions.

**Teacher** – All of the below. **Senior Teacher** – All of the below plus: **Supervised Classes**, **Supervised Staff**, **Fee Balances**.

Log in and you should see the **Teacher Dashboard** with:

| Feature | How to test |
|--------|--------------|
| **Sign in** | Use teacher email/password; land on Teacher Dashboard |
| **My Classes** | Tap "My Classes" → view students (assigned to you via backend) |
| **Mark Attendance** | Tap "Mark Attendance" → select date, class, stream → mark present/absent |
| **Attendance Records** | From Mark Attendance or back → view history |
| **Transport** | Tap "Transport" → view routes/allocations |
| **Diary** | Tap "Diary" → view homework you’ve set |
| **Assignments** | Tap "Assignments" → issue/view homework |
| **My Profile** | Tap "My Profile" → view your staff profile |
| **My Salary** | Tap "My Salary" → view payroll/payslips (if staff_id linked) |
| **Timetable** | Tap "My Timetable" → view your teaching schedule |
| **Lesson Plans** | Tap "Lesson Plans" → list/create lesson plans |
| **Exams & Marks** | Tap "Exams & Marks" → list exams → enter results (exam detail flow) |
| **Notifications** | Tap bell icon in header |
| **Settings** | Tap gear icon in header |
| **Leave** | Tap "Leave" → view/request leave (uses `staff_id` for teachers) |
| **Supervised Classes** (senior teacher only) | Tap "Supervised Classes" → classrooms you supervise |
| **Supervised Staff** (senior teacher only) | Tap "Supervised Staff" → staff you supervise |
| **Fee Balances** (senior teacher only) | Tap "Fee Balances" → student fee balances under your supervision |

Ensure your Laravel API exposes the endpoints the app calls (e.g. `/api/...`) and that:
- Teacher/senior teacher users have `staff_id` or `teacher_id` set for profile, salary, leave, and teacher-scoped data.
- Backend returns a `role` the app can normalize (e.g. "Teacher", "Senior Teacher", "senior_teacher").
- For senior teacher screens, expose `/api/senior-teacher/supervised-classrooms`, `/api/senior-teacher/supervised-staff`, `/api/senior-teacher/fee-balances` (or equivalent).

---

## Compile & Run (Quick Reference)

1. **Backend** (project root):  
   `php artisan serve --host=0.0.0.0 --port=8000`

2. **Mobile app** (first terminal):  
   `cd mobile-app` → `npm install` → `npm start`

3. **Build & run Android** (second terminal):  
   `cd mobile-app` → `npx react-native run-android`

4. **API URL**: In `mobile-app/.env`, set `API_BASE_URL` to:
   - Emulator: `http://10.0.2.2:8000` (or your API base)
   - Physical device: `http://YOUR_PC_IP:8000`

---

## Testing with Browser (Alternative - React Native Web)

If you **really** need to test in a browser, you'll need to:

1. Install React Native Web:
```powershell
npm install react-native-web react-dom
```

2. Set up Webpack/Expo Web
3. Configure web entry point
4. Build for web

**This requires significant additional setup and is NOT included in Phase 1.**

---

## Troubleshooting

### "Command not found: adb"
- Android SDK not in PATH
- Restart terminal after setting environment variables

### "No devices/emulators found"
- Start emulator first: Open Android Studio → AVD Manager → Start
- Or connect physical device

### "Metro bundler port 8081 in use"
```powershell
npx react-native start --reset-cache
```

### "Unable to connect to server"
- Check Laravel server is running: `http://localhost:8000/api`
- For emulator: use `10.0.2.2:8000` in .env
- For device: use your computer's IP address

### App crashes on launch
- Check Metro bundler is running
- Clear cache: `npx react-native start --reset-cache`
- Rebuild: `npx react-native run-android`

---

## Current Status

✅ **Completed:**
- Teacher flow: full stack (Dashboard, My Classes, Attendance, Timetable, Assignments, Lesson Plans, Diary, Transport, My Profile, My Salary, Exams, Settings, Notifications)
- Teacher navigator wired; teachers see real screens instead of placeholders
- Build and run steps documented above

⏳ **Next Steps:**
1. Ensure Laravel API serves the routes the app uses (students, attendance, academics, staff, payroll, transport, etc.) under the same base URL as `API_BASE_URL`
2. Start Laravel backend, then Metro, then `npx react-native run-android`

---

## Need Help?

- Android Studio setup: [reactnative.dev/docs/environment-setup](https://reactnative.dev/docs/environment-setup)
- React Native docs: [reactnative.dev](https://reactnative.dev)
- Troubleshooting: [reactnative.dev/docs/troubleshooting](https://reactnative.dev/docs/troubleshooting)
