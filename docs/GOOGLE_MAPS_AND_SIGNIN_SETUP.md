# Google Maps SDK + Sign in with Google (zero-cost setup)

Use this with Google Cloud project **My First Project** (or your school project). Goal: live bus maps and Google account linking **without paying Google**.

## What is free

| Capability | Google product | Cost |
|------------|----------------|------|
| In-app map (Android/iOS) | **Maps SDK** without a Cloud Map ID | Unlimited, $0 |
| Device GPS | `expo-location` (your phones) | $0 |
| Store lat/lng/time | Your Laravel DB | $0 |
| Sign in / link Google | Google Identity / OAuth | $0 |

## Do not enable (these can bill you)

- Geocoding API
- Places API
- Directions / Routes / Distance Matrix / Roads
- Maps JavaScript API (browser maps; 10k free then paid)
- Cloud **Map IDs** on mobile maps (turns a load into billable **Dynamic Maps**)

## Console checklist

1. **Billing**  
   Link a billing account (required even for free Maps SDK).  
   Create a **budget alert at $1** (Billing → Budgets & alerts).

2. **Enable APIs** (APIs & Services → Library)  
   - Maps SDK for Android  
   - Maps SDK for iOS  
   Do **not** enable Geocoding, Places, Directions, etc.

3. **Maps API keys** (APIs & Services → Credentials → Create credentials → API key)  
   Create **two** keys (Users app and Admin app).

   **Users key** (`EXPO_PUBLIC_GOOGLE_MAPS_API_KEY` in users / iOS builds):  
   - Application restriction: Android apps → package `com.royalkingsschools.users` + SHA-1 (debug + EAS/Play upload).  
   - Application restriction: iOS apps → bundle `com.royalkingsschools.users`.  
   - API restriction: Maps SDK for Android + Maps SDK for iOS only.

   **Admin key**: same pattern for `com.royalkingsschools.admin`.

4. **OAuth consent screen**  
   External or Internal as appropriate. Add scopes: `openid`, `email`, `profile`.

5. **OAuth client IDs** (Credentials → Create → OAuth client ID)  
   - **Web** client → Laravel `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET`, redirect  
     `{APP_URL}/auth/google/callback`. Also set mobile `EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID` to this Web client (ID tokens from Expo often use the Web client as `aud`).  
   - **Android** clients for package names + SHA-1 → `EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID`.  
   - **iOS** clients for bundle IDs → `EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID`.

6. **Laravel `.env`**

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

7. **Mobile `mobile-app/.env`** (or EAS secrets)

```env
EXPO_PUBLIC_GOOGLE_MAPS_API_KEY=
EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID=
EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID=
EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID=
```

Rebuild a **new native binary** after adding Maps / location plugins. OTA (EAS Update) cannot add native modules.

## App behaviour (cost-safe)

- Driver marks a child’s morning pickup or evening drop-off with **Use my location** or **tap the map**. No address search.
- Live tracking uses your API (driver ping ~15s, parent/admin poll ~5s). No Google Fleet Engine.
- Web admin keeps a **list** of live buses (no Maps JavaScript embed).

## Android package + EAS SHA-1 (Maps API key restriction)

Paste these into **Keys and credentials → your Maps key → Application restrictions → Android apps**.

| App | Package name | EAS upload SHA-1 |
|-----|--------------|------------------|
| Royal Kings Users | `com.royalkingsschools.users` | `79:2F:E3:50:F3:4F:DC:8B:91:4A:2E:79:D1:A0:51:6F:F5:37:D6:FE` |
| Royal Kings Admin | `com.royalkingsschools.admin` | `7B:2C:B5:12:A2:4C:DC:62:23:64:A6:BC:C4:4B:8C:73:D5:CF:B3:FC` |
| Edulynk | `com.edulynk.app` | `1F:E6:D1:7B:24:A9:42:91:0F:91:61:30:C9:FB:DB:37:02:4C:86:D6` |

If the app is on **Google Play** with Play App Signing, also add the **App signing key certificate** SHA-1 from Play Console → App → Setup → App integrity (that SHA-1 differs from the EAS upload key).

iOS Maps keys only need the bundle IDs: `com.royalkingsschools.users`, `com.royalkingsschools.admin`, `com.edulynk.app`.

## Why Sign in with Google (and wrong-account behaviour)

**What it helps**

- Faster return visits (no typing password/OTP every time).
- Links an existing school user to a verified Google email — **never creates new accounts**.
- Same school session after link: parents still see their children, staff keep roles/permissions.
- Optional post-login prompt (admin-controlled) nudges people to link once; **Skip** is always allowed for that session.

**Admin controls** (web): Students → **Google sign-in prompt**

- Mode **Everyone** (default): after password/OTP, ask anyone not yet linked.
- Mode **Selected users only**: only people you mark.
- Mode **Off**: no post-login prompt (login-screen Continue with Google still works if client IDs are set).

**If someone picks a different Google account**

| Situation | Result |
|-----------|--------|
| Google email matches their school account (or already linked `google_id`) | Sign-in / link succeeds |
| Google email belongs to **another** school user | Error: already linked / belongs to a different account |
| Google email is **not** in the school system | Login fails (“No account found…”). Linking while already signed in may attach that Google to the current user only if the email is not owned by someone else |
| Already linked, then tries a second Google | Must unlink first in Profile |

Local Expo: put client IDs in `mobile-app/apps/<users|admin|edulynk>/.env`, then restart Metro (`npx expo start -c`) so `androidClientId` loads — otherwise the button stays hidden.

**Expo Go vs installed app:** Google Sign-In **does not work in Expo Go** (`Error 400: redirect_uri_mismatch`). Test it on a **preview / production APK** (EAS build) or a development build. Password and OTP still work in Expo Go.
