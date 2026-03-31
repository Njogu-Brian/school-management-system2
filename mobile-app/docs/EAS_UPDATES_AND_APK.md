# OTA updates (EAS Update) and APK downloads

## Two different mechanisms

| What | How it works |
|------|----------------|
| **OTA update** (Expo / EAS Update) | New **JavaScript bundle** only. User taps “Update now” in the app → app reloads. No new APK unless native code or `runtimeVersion` changes. |
| **Full APK** | New installable file. Host on **S3 + HTTPS** (or CloudFront), set a **stable URL** and upload/replace the file each release. |

---

## Test OTA updates (prompt in app)

1. **Build and install** an app binary that is tied to your Expo project and **channel**:
   - EAS: `cd mobile-app && npx eas build -p android --profile apk`
   - Install the resulting APK on a device.

2. **`runtimeVersion`** in `app.config.js` must stay the **same** for OTA to apply. It is `1.0.0` unless you set `EXPO_RUNTIME_VERSION`.

3. **Publish a JS update** to the same channel as the profile (`preview` for `apk` / `preview` in `eas.json`):
   ```bash
   cd mobile-app
   npx eas update --channel preview --message "Test: change something visible"
   ```
   Make a small visible change in JS first (e.g. a label on the login screen), so you can confirm the update.

4. **Open the app** (it already checks on load via `checkAutomatically: 'ON_LOAD'`). You can also use **Settings → Check for Updates**.

5. If nothing appears, confirm in [Expo dashboard](https://expo.dev) that the update is published under project `school-erp-mobile` and channel **`preview`**.

**Note:** Local-only `gradlew assembleRelease` builds still embed Expo config, but **EAS Update** is most reliable when you use **`eas build`** for the binary you install. If you only use Gradle locally, prefer testing OTA with an **EAS-built** APK.

---

## “Latest APK” link (portal + API)

1. **AWS:** Upload `app-release.apk` to S3 (or use CloudFront). Use a **fixed object key** you overwrite each release, e.g. `downloads/RoyalKingsERP-latest.apk`, so the URL never changes.

2. **Laravel `.env`:**
   ```env
   MOBILE_APP_DOWNLOAD_URL=https://your-cdn-or-bucket-url/downloads/RoyalKingsERP-latest.apk
   ```

3. **Deploy** so config is loaded. The **web login** page shows “Download Android app (latest APK)” when this is set.

4. **`GET /api/app-branding`** returns `android_apk_download_url` for the mobile login screen (Android only).

---

## Bump runtime (when OTA is not enough)

If you change native code, native modules, or need to break OTA compatibility, bump **`runtimeVersion`** in `app.config.js` (or `EXPO_RUNTIME_VERSION`), then ship a **new APK** (EAS or Gradle) and publish new updates against that runtime.
