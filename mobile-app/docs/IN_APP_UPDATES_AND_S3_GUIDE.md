# In-app updates and S3 distribution (step-by-step)

This guide explains how staff get **updates inside the app** without reinstalling from scratch every time, and how **Amazon S3** fits in when you are not using the Play Store.

---

## Two kinds of updates (do not confuse them)

| Type | What changes | User experience | When you need it |
|------|----------------|-----------------|------------------|
| **OTA (over-the-air)** | JavaScript/UI only (`expo-updates`) | Settings → **Check for Updates** → app reloads in ~10–30s | Bug fixes, labels, API URLs, most screens |
| **Full APK** | Native code, new permissions, `runtimeVersion` bump | User downloads APK from S3 (or link) and installs | New native module, Android SDK change, breaking OTA |

**S3 is mainly for hosting the APK file** at a stable HTTPS URL.  
**OTA updates** are delivered by **Expo EAS Update** (hosted on Expo’s CDN by default). You do **not** need S3 for routine JS updates if you use EAS Update.

---

## What is already in this project

- `expo-updates` in `mobile-app/package.json`
- `app.config.js`: `runtimeVersion`, `updates.url` → Expo project `d8b53a3a-3093-407c-b552-de66fc1cc8bb`
- `eas.json` profiles: `apk`, `preview`, `production` with `EXPO_PUBLIC_UPDATES_ENABLED=1`
- Settings → **Check for Updates** → `checkForAppUpdate()` in `src/services/update.service.ts`
- Laravel: `MOBILE_APP_DOWNLOAD_URL` → login page + `/api/app-branding` → `android_apk_download_url` for APK fallback

**Important:** OTA only works if the installed APK was built with **EAS Build** (or a local release build that embedded `EXPO_PUBLIC_UPDATES_ENABLED=1`). A plain `gradlew assembleRelease` without that flag will **not** receive OTA; users must use the S3 APK link instead.

---

## Part A — One-time setup (your side)

### A1. Create an Expo account and link the project

1. Install EAS CLI (on your PC):
   ```bash
   npm install -g eas-cli
   ```
2. Log in:
   ```bash
   eas login
   ```
3. From the mobile app folder:
   ```bash
   cd mobile-app
   eas whoami
   ```
   Project ID is already in `app.config.js`: `d8b53a3a-3093-407c-b552-de66fc1cc8bb`.

### A2. Build the “base” APK (do this once per `runtimeVersion`)

1. Ensure production API URL is correct in `eas.json` (`EXPO_PUBLIC_API_BASE_URL`).
2. Build an installable APK:
   ```bash
   cd mobile-app
   npx eas build -p android --profile apk
   ```
3. Wait for the build on [expo.dev](https://expo.dev) → download the APK.
4. Distribute that APK to all devices (USB, email, or S3 — see Part C).

**Keep the same `runtimeVersion`** in `app.config.js` (default `1.0.0`) for all OTA publishes until you ship a new native APK.

### A3. Publish your first OTA update (JS-only change)

1. Make a visible change (e.g. fix “Pending” fee badge text).
2. Publish to the **same channel** as the build profile (`apk` uses channel `preview`):
   ```bash
   cd mobile-app
   npx eas update --channel preview --message "Fix fee badge and tab bar"
   ```
3. On a device with the EAS-built APK installed:
   - Open app → **Settings** → **Check for Updates** → **Update now**, or
   - Close and reopen the app (checks on load when OTA is enabled).

---

## Part B — Day-to-day: ship fixes without a new APK

### B1. Developer workflow

1. Change code under `mobile-app/src/`.
2. Test locally with `npx expo start` (OTA is off in dev; that is normal).
3. Publish:
   ```bash
   cd mobile-app
   npx eas update --channel preview --message "Describe the fix"
   ```
   For production staff builds use `--channel production` if your APK was built with the `production` profile.

### B2. What staff do

1. Open **Royal Kings ERP**.
2. Go to **Settings** (or **More** → **Settings** for teachers).
3. Tap **Check for Updates**.
4. If an update exists → **Update now** → app restarts with new JS.

No uninstall, no new APK, as long as `runtimeVersion` did not change.

### B3. When you **must** ship a new APK

Bump `runtimeVersion` in `app.config.js` (e.g. `1.0.1`) and run a new `eas build` when you:

- Add/remove a native dependency (new Expo module, etc.)
- Change `android` permissions or `app.config.js` plugins
- Change `expo-build-properties` in a way that affects the binary

Then upload the new APK to S3 (Part C) and ask users to install once. After that, OTA works again for that runtime.

---

## Part C — Amazon S3 for APK hosting (no Play Store)

Use S3 when you need a **single download link** for first install or when OTA is not available.

### C1. Create the bucket and object

1. AWS Console → **S3** → **Create bucket**
   - Name e.g. `royal-kings-erp-downloads`
   - Region: closest to Kenya (e.g. `af-south-1`) or your choice
   - Block public access: you can keep blocked if you use CloudFront or presigned URLs; for a simple public APK, allow public read on one object only (see below).

2. Upload the APK:
   - Key (path): `mobile/RoyalKingsERP-latest.apk` (fixed name — overwrite each release)
   - Content-Type: `application/vnd.android.package-archive`

3. Make the object readable:
   - **Option 1 (simple):** Bucket policy allowing `s3:GetObject` on `mobile/*` for public IP ranges (only if acceptable for your security policy).
   - **Option 2 (recommended):** **CloudFront** distribution in front of the bucket, HTTPS URL, no public bucket.

Example stable URL:
`https://d1234abcd.cloudfront.net/mobile/RoyalKingsERP-latest.apk`

### C2. Point the portal to that URL

In Laravel `.env` on the server:

```env
MOBILE_APP_DOWNLOAD_URL=https://d1234abcd.cloudfront.net/mobile/RoyalKingsERP-latest.apk
```

Deploy / `php artisan config:clear`.

Effects:

- Web login page can show “Download Android app”.
- `GET /api/app-branding` returns `android_apk_download_url` for the mobile login screen.
- **Check for Updates** can open this link when OTA is disabled or fails.

### C3. Release process when shipping a new APK

1. `npx eas build -p android --profile apk`
2. Download APK from Expo.
3. Upload to S3 **same key** (overwrite `RoyalKingsERP-latest.apk`).
4. Optional: bump `version` in `app.config.js` for display only.
5. Notify staff: “Install update from the link on the login page” (only when APK changed, not for OTA).

---

## Part D — Optional: host OTA bundles on S3 (advanced)

Expo’s default OTA server is `https://u.expo.dev/<project-id>`. Moving **only** the manifest to S3 while keeping Expo’s protocol is possible but non-trivial (custom update server or mirroring Expo exports).

**Recommendation:** Use **EAS Update** for OTA (Part B) and **S3 only for APK** (Part C). That matches how this repo is configured and avoids maintaining your own update API.

If you later need full self-hosting, see Expo docs: [Custom updates server](https://docs.expo.dev/eas-update/self-hosted/).

---

## Part E — Checklist and troubleshooting

### E1. “Check for Updates” says production builds only

- You are in Expo Go or a debug build. Install the **EAS-built APK**.

### E2. “Up to date” but code did not change

- Update published to wrong **channel** (must match `eas.json` profile channel).
- Wrong **runtimeVersion** on device vs published update.
- Confirm on [expo.dev](https://expo.dev) → project → **Updates** → channel `preview` / `production`.

### E3. Update download fails

- Device needs internet to Expo CDN (`u.expo.dev`).
- Firewall blocking; try mobile data.
- Fall back: set `MOBILE_APP_DOWNLOAD_URL` and use APK from S3.

### E4. Fee badge still shows “Pending (6000)”

- Device is on an **old APK/OTA bundle**. Publish OTA (Part B) or reinstall latest APK.
- Teachers should see **Pending** only; amounts only for Senior Teacher / Super Admin roles.

### E5. Bottom tab bar “floating” above system buttons

- Fixed in latest JS via `tabBarConfig.tsx` + Android `navigationBarColor`.
- Ship OTA or new APK; on Android 15+ edge-to-edge, tab bar should sit flush above system nav.

---

## Quick reference commands

```bash
# Build installable APK (OTA-enabled)
cd mobile-app
npx eas build -p android --profile apk

# Publish JS update (no new APK)
npx eas update --channel preview --message "Your message"

# Production channel
npx eas update --channel production --message "Your message"
```

---

## Summary

1. **First time:** `eas build` → install APK (via S3 link or direct).
2. **Every fix:** `eas update` → users **Check for Updates** in the app.
3. **S3:** stable HTTPS URL for the APK; set `MOBILE_APP_DOWNLOAD_URL` in Laravel.
4. **New APK:** only when `runtimeVersion` or native code changes.

For questions about channels/runtime, see also `mobile-app/docs/EAS_UPDATES_AND_APK.md`.
