# Sprint 1 — Batch 2.1 Report: Google Sign-In & Biometrics

**Status:** Complete  
**Scope:** Extend Batch 2 authentication with Google OAuth and device biometrics. Password login unchanged.  
**Verification:** `tsc --noEmit` passes for `packages/core` and `apps/admin` (strict mode).

---

## 1. Objective

Extend the Admin App authentication architecture with:

1. **Google Sign-In** — OAuth ID token flow + backend exchange, Google button on login, identity in Auth Context.
2. **Biometrics** — Face ID / fingerprint / device biometrics via Expo Local Authentication; first login requires credentials; optional enrollment after success; subsequent unlock of an existing session only.

Session rules enforced:

| Rule | Implementation |
| --- | --- |
| First login requires credentials | Biometric unlock hidden until user enables after password/Google login |
| Subsequent logins may use biometrics | `hasBiometricUnlockAvailable()` + login screen unlock card |
| Biometrics only unlock existing session | `BiometricUnlockStrategy` reads SecureStore bundle → `GET /user` with token — no `POST /login` |
| Biometrics do not replace backend auth | Sanctum token still validated server-side on every unlock |

---

## 2. Architecture (strategy pattern)

```
SessionProvider
  AuthProvider          ← orchestrator: completeAuth(), login, logout, enrollment
    GoogleAuthProvider  ← React: OAuth config + signInWithIdToken()
      BiometricAuthProvider ← React: unlock availability, type label, unlock()
        AdminRootNavigator
```

**Strategy classes** (`packages/core/src/auth/providers/`):

| Class | Method | Backend call |
| --- | --- | --- |
| `PasswordAuthProvider` | `password` | `POST /login` |
| `GoogleSignInStrategy` | `google` | `POST /login/google` |
| `BiometricUnlockStrategy` | `biometric` | `GET /user` (explicit bearer token only) |

All strategies return `AuthProviderResult` → `establishSessionFromResult()` persists token + user consistently.

Additional providers (OTP, SSO) can implement `IAuthProvider` and plug into `AuthProvider.runAuth()` without changing the UI shell.

---

## 3. Files created

### `@erp/core` — providers & storage

| File | Purpose |
| --- | --- |
| `auth/providers/types.ts` | `AuthMethod`, `IAuthProvider`, `AuthProviderResult` |
| `auth/providers/establishSession.ts` | Shared session persistence after any strategy |
| `auth/providers/PasswordAuthProvider.ts` | Password strategy |
| `auth/providers/GoogleAuthProvider.ts` | `GoogleSignInStrategy` — ID token → backend |
| `auth/providers/BiometricAuthProvider.ts` | `BiometricUnlockStrategy` — device unlock + profile validation |
| `auth/providers/index.ts` | Barrel |
| `auth/googleIdentity.ts` | Decode Google ID token payload (`sub`, `email`, …) |
| `auth/GoogleAuthContext.tsx` | React `GoogleAuthProvider` + `useGoogleAuth()` |
| `auth/BiometricAuthContext.tsx` | React `BiometricAuthProvider` + `useBiometricAuth()` |
| `storage/biometricStorage.ts` | Expo Local Authentication + admin-scoped SecureStore bundle |

### Admin app — UI

| File | Purpose |
| --- | --- |
| `features/auth/components/GoogleSignInButton.tsx` | `expo-auth-session` ID token flow → `signInWithIdToken` |
| `features/auth/screens/BiometricEnableScreen.tsx` | Post-login enrollment (“Enable Face ID?”) |
| `docs/execution/batch-2-1-report.md` | This report |

---

## 4. Files modified

| File | Change |
| --- | --- |
| `packages/core/src/types/auth.ts` | `GoogleIdentity`, `GoogleLoginRequest` |
| `packages/core/src/types/user.ts` | `googleId`, `googleEmail` |
| `packages/core/src/config/env.ts` | `GOOGLE_*_CLIENT_ID`, `hasGoogleOAuthConfig()` |
| `packages/core/src/storage/keys.ts` | Biometric AsyncStorage + SecureStore keys |
| `packages/core/src/storage/index.ts` | Export biometric storage |
| `packages/core/src/api/auth.api.ts` | `loginWithGoogle`, `getProfileWithToken` |
| `packages/core/src/api/client.ts` | `getWithToken`; interceptor skips when `Authorization` preset |
| `packages/core/src/auth/AuthContext.tsx` | Strategy orchestration, `googleIdentity`, biometric enrollment |
| `packages/core/src/auth/index.ts` | Export new providers/contexts |
| `packages/core/package.json` | Peer: `expo-local-authentication` |
| `apps/admin/App.tsx` | `GoogleAuthProvider` → `BiometricAuthProvider` nesting |
| `apps/admin/app.config.ts` | Google env in `extra`, `expo-local-authentication` plugin |
| `apps/admin/package.json` | `expo-auth-session`, `expo-web-browser`, `expo-local-authentication`, etc. |
| `apps/admin/src/features/auth/screens/LoginScreen.tsx` | Google button, biometric unlock card |
| `apps/admin/src/features/auth/index.ts` | Export new screens/components |
| `apps/admin/src/navigation/AdminRootNavigator.tsx` | `BiometricEnableScreen` gate |

---

## 5. Deliverables checklist

| Deliverable | Location |
| --- | --- |
| Updated Login Screen | `LoginScreen.tsx` — password + Google + biometric unlock |
| Google Sign-In flow | `GoogleSignInButton.tsx` + `GoogleSignInStrategy` + `POST /login/google` |
| Biometric unlock flow | `BiometricUnlockStrategy` + login unlock card |
| Provider abstraction | `IAuthProvider` + strategy classes + React provider trio |
| Session integration | `establishSessionFromResult` + enrollment updates bundle |
| AuthProvider | `AuthContext.tsx` (`AuthProvider`) |
| GoogleAuthProvider | `GoogleAuthContext.tsx` |
| BiometricAuthProvider | `BiometricAuthContext.tsx` |

---

## 6. User flows

### Google Sign-In

1. User taps **Continue with Google**.
2. `expo-auth-session` returns an ID token.
3. `GoogleSignInStrategy` → `POST /login/google` with `{ id_token }`.
4. Backend validates token (Google tokeninfo), issues Sanctum token.
5. `AuthProvider` stores `googleIdentity` (decoded JWT) + `user.googleId` / `googleEmail`.
6. If device supports biometrics and not yet enabled → `BiometricEnableScreen`.

### Biometric enrollment (after first credential login)

1. Password or Google login succeeds.
2. `biometricEnrollmentPending === true` → `BiometricEnableScreen`.
3. **Enable** → `setBiometricEnabled(true)` + `saveBiometricAuthBundle(token)` with `requireAuthentication: true`.
4. **Not now** → skip; user can enable later (future Settings batch).

### Biometric unlock (returning user)

1. Login screen shows **Unlock with Face ID / Fingerprint** when bundle exists.
2. `LocalAuthentication.authenticateAsync` (device UI).
3. Read token from SecureStore (requires biometric to access bundle).
4. `GET /user` with explicit bearer → restore session.
5. No call to `/login` or `/login/google`.

---

## 7. Backend dependencies

### Required Laravel endpoints (already implemented)

| Method | Path | Purpose | Controller |
| --- | --- | --- | --- |
| `POST` | `/api/login/google` | Exchange Google ID token for Sanctum session | `AuthApiController::loginWithGoogle` |
| `GET` | `/api/user` | Validate token / load profile (biometric unlock) | `AuthApiController::user` |
| `POST` | `/api/login` | Password login (unchanged) | `AuthApiController::login` |
| `POST` | `/api/logout` | Revoke token (unchanged) | `AuthApiController::logout` |

**Google login request body:** `{ "id_token": "<JWT from Google>" }`  
**Response:** Same as password login — `{ success, data: { token, user, expires_at } }`.

**Server requirements (existing):**

- `config('services.google.client_id')` must match the OAuth client used by the mobile app (audience check in `loginWithGoogle`).
- User must exist (or link flow via email) — backend returns `404` if no account for Google email.

### Optional future backend enhancements

| Enhancement | Benefit |
| --- | --- |
| Return `google_id` / `google_email` in `formatUserForApi` | Avoid client-side JWT decode for identity display |
| Dedicated biometric flag endpoint | Server-side audit of device unlock (not required for current design) |
| Refresh token endpoint | Longer sessions without re-login (Batch 2 slot already reserved) |

---

## 8. Environment / configuration

Set in Admin app `.env` or EAS secrets (mirrors Staff App):

```env
EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID=...
EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID=...
EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID=...
```

Passed through `app.config.ts` → `expo.extra` → `@erp/core` `config/env.ts`.

**Laravel:** `GOOGLE_CLIENT_ID` / `services.google.client_id` must align with the **web** client ID used for token verification (same as Staff App).

---

## 9. Security considerations

### Google Sign-In

- ID token is validated **server-side** via Google `tokeninfo` — client decode is for UX only (`googleIdentity` in context).
- Audience (`aud`) must match configured `services.google.client_id` or login is rejected.
- Email must be verified (`email_verified === true`).
- Sanctum token still subject to 7-day expiry and 401 handling.

### Biometrics

- **No password stored** — only the Sanctum token in SecureStore with `requireAuthentication: true` (Keychain/Keystore biometric gate).
- Unlock always followed by **`GET /user`** — expired/revoked tokens fail and increment failure counter.
- **5 failed attempts** → biometric unlock disabled until next successful password/Google login (`BIOMETRIC_MAX_FAILURES`).
- Biometric unlock does **not** call `/login` — cannot create a session without a prior backend-authenticated login.
- Logout clears the biometric bundle (`clearBiometricAuthBundle`) but leaves the “enabled” preference; user must sign in again to refresh the bundle.
- Admin keys are prefixed (`admin_erp_*`) so Staff and Admin apps do not share biometric storage on the same device.

### General

- API client does not overwrite an explicit `Authorization` header (important for pre-session token validation during biometric unlock).
- Explicit-deny role guards unchanged — Google login still passes through `canAccessApp(user, 'admin')`.

---

## 10. Blockers

None for implementation. **Operational blockers for testing:**

1. **Google OAuth client IDs** must be configured for the Admin app binary (Android/iOS) and match Laravel `services.google.client_id`.
2. **Physical device or emulator with biometrics enrolled** required to test unlock (simulator Face ID on iOS works).
3. **Staff App symmetric guard** (`Admin → Staff App denied`) remains in Staff App codebase — not part of this batch.

---

## 11. Provider tree (final)

```
GestureHandlerRootView
  ThemeProvider
    SafeAreaProvider
      AppErrorBoundary
        SessionProvider
          AuthProvider
            GoogleAuthProvider
              BiometricAuthProvider
                AdminRootNavigator (RootGate)
```

`RootGate` states: initializing → login → access denied → **biometric enrollment** → app.
