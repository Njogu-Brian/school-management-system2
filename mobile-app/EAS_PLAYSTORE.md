# EAS / Play Store builds (Users + Admin)

Never run `eas build` from the **repo root**. That creates the wrong Expo project (`school-management-system2`) and fails versioning.

## Apps (separate Play packages)

| App | Folder | Play package | Current target |
|-----|--------|--------------|----------------|
| **Users** | `mobile-app/apps/users` | `com.royalkingsschools.users` | `1.0.5` / versionCode **8** (native `android/app/build.gradle`) |
| **Admin** | `mobile-app/apps/admin` | `com.royalkingsschools.admin` | `1.0.14` / versionCode **15** (`app.config.ts`) |

Bump **before** each Play upload so codes stay above the Console “Active” row.

## Local builds

From repo root:

```powershell
.\scripts\eas-build-playstore.ps1              # both apps, production
.\scripts\eas-build-playstore.ps1 -App users
.\scripts\eas-build-playstore.ps1 -App admin
```

Or manually:

```powershell
cd mobile-app\apps\users
eas build --platform android --profile production --non-interactive --no-wait

cd ..\admin
eas build --platform android --profile production --non-interactive --no-wait
```

## GitHub Actions

- `.github/workflows/eas-build-users.yml`
- `.github/workflows/eas-build-admin.yml`

Requires repo secret `EXPO_TOKEN`. Trigger via **Actions → workflow_dispatch**, or push to `main` when app paths change.

## EAS Workflows (per app)

- `mobile-app/apps/users/.eas/workflows/production-android.yml`
- `mobile-app/apps/admin/.eas/workflows/production-android.yml`

```powershell
cd mobile-app\apps\users
eas workflow:run .eas/workflows/production-android.yml

cd ..\admin
eas workflow:run .eas/workflows/production-android.yml
```
