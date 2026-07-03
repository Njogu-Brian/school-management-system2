# Royal Kings Admin — Play Store Release Guide

## App identity (Google Play Console)

| Field | Value |
|--------|--------|
| App name | **Royal Kings Admin** |
| Package name | **`com.royalkingsschools.admin`** (cannot be changed after creation) |
| Default language | English (United Kingdom) |
| App or game | **App** |
| Free or paid | **Free** (internal staff tool) |

Use the same package name in Play Console as in `apps/admin/app.config.ts`.

---

## What changed in this release prep

- **Google Sign-in removed** from mobile admin app and web login page (admin creates credentials).
- **Login branding** loads from `GET /api/app-branding`: school name, logo, login background, portal colors.
- **Biometric unlock** stores the session token securely; enabling biometrics requires fingerprint/face verification; unlock refreshes the token when possible.
- **Sessions**: Sanctum token + remember-me idle timeout; expired sessions redirect to login (no refresh loop).

---

## 1. Preview locally with Expo Go

```powershell
cd E:\school-management-system2\school-management-system2\mobile-app\apps\admin
npm.cmd install
npx.cmd expo start
```

- Install **Expo Go** on your Android phone.
- Same Wi‑Fi: scan the QR code.
- Different network: `npx.cmd expo start --tunnel`

**Test checklist before building:**

1. Login page shows **Royal Kings** logo, name, purple brand colors, and background image.
2. Sign in with admin email + password (no Google button).
3. After login, accept **Enable biometrics** if prompted.
4. Log out → login screen shows **Use Fingerprint/Face ID** → unlock works.
5. Force-close app → reopen → still signed in (if “Keep me signed in” was on).

---

## 2. One-time EAS setup

Install EAS CLI globally (once):

```powershell
npm install -g eas-cli
eas login
```

Link the project (run from `apps/admin`):

```powershell
cd E:\school-management-system2\school-management-system2\mobile-app\apps\admin
eas init
```

This creates an Expo project ID and writes it to `app.config.ts` → `extra.eas.projectId`.

---

## 3. Build an APK for internal testing

```powershell
cd E:\school-management-system2\school-management-system2\mobile-app\apps\admin
eas build --platform android --profile preview
```

When the build finishes, EAS gives a download link. Install the APK on your phone and repeat the test checklist.

---

## 4. Build for Play Store (AAB)

```powershell
eas build --platform android --profile production
```

This produces an **Android App Bundle** (`.aab`) required by Google Play.

Download the `.aab` from the EAS dashboard when the build completes.

---

## 5. Publish on Google Play Console

### Create the app (your current screen)

1. **App name:** Royal Kings Admin  
2. **Default language:** English (United Kingdom)  
3. **App or game:** App  
4. **Free or paid:** Free  
5. Click **Create app**

### Before first upload

Complete these Play Console sections:

| Section | Notes |
|---------|--------|
| **App content** | Privacy policy URL (required). Use your school website privacy page. |
| **Target audience** | 18+ (staff/admin tool) |
| **Data safety** | Declare email, auth tokens, device identifiers if collected |
| **Store listing** | Short + full description, screenshots (phone), feature graphic 1024×500 |
| **App icon** | 512×512 PNG (use school logo; replace `assets/icon.png` with 1024×1024 for best quality) |

### Upload the AAB

1. **Release → Testing → Internal testing** (recommended first)  
2. **Create new release**  
3. Upload the `.aab` from EAS  
4. Add release notes, e.g. “Initial admin release — staff login, dashboard, students, finance.”  
5. Review and **Start rollout to Internal testing**

Add tester emails under **Internal testing → Testers**.

### Promote to production

After internal testing passes:

1. **Release → Production → Create new release**  
2. Upload the same (or newer) AAB  
3. Complete **Country/region** availability  
4. Submit for review  

Google review typically takes 1–7 days.

---

## 6. Optional: submit from CLI

After a production build:

```powershell
eas submit --platform android --profile production
```

Requires a Google Play service account JSON key configured in EAS (`eas credentials`).

---

## 7. App icons

Icons are in `mobile-app/apps/admin/assets/`:

- `icon.png` — app icon  
- `adaptive-icon.png` — Android adaptive foreground  
- `splash-icon.png` — splash screen  

For Play Store listing, export a **512×512** PNG from your school logo. For best results, replace `icon.png` with a **1024×1024** version before running `eas build production`.

---

## 8. Deploy web login changes

The Google button was removed from `resources/views/auth/login.blade.php`. Deploy Laravel to production so web login matches mobile:

```bash
git pull
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| `npx` blocked in PowerShell | Use `npx.cmd expo start` |
| Biometrics not offered | Device must have fingerprint/face enrolled; sign in with password first |
| Branding not loading | Check `GET /api/app-branding` returns 200 on production |
| Play Console package mismatch | Package must be exactly `com.royalkingsschools.admin` |
