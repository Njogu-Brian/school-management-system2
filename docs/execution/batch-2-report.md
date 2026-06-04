# Sprint 1 — Batch 2 Report: Authentication Foundation

**Status:** Complete
**Scope:** Authentication foundation only. No business features.
**Verification:** `tsc --noEmit` passes for `apps/admin`, `packages/core`, and `packages/ui` (strict mode). No lint errors.

---

## 1. Objective

Build the authentication foundation for the Admin App: canonical domain types, secure token
storage, a session + auth provider pair, a login screen, a logout flow, and route guards that
enforce app-level access — integrating with the existing Laravel Sanctum endpoints.

Explicitly excluded (per the brief): MFA, Google login, biometric login.

---

## 2. Backend contract (verified against the codebase)

Read from `app/Http/Controllers/Api/AuthApiController.php` and `routes/api.php`:

| Endpoint | Auth | Response shape |
| --- | --- | --- |
| `POST /login` | public | `{ success, data: { token, user, expires_at } }`; `401` on bad credentials |
| `GET /user` | `auth:sanctum` | `{ success, data: user }` |
| `POST /logout` | `auth:sanctum` | `{ success, message }` (revokes current token) |

`user` payload (`formatUserForApi`): `{ id, name, email, role (display name string), permissions (string[]), staff_id?, teacher_id?, parent_id?, phone?, avatar? }`.

Key findings that shaped the design:
- **`expires_at` is returned by login** (ISO8601, 7-day Sanctum token). Used directly for graceful expiry handling.
- **No `school` / `branch` fields exist yet.** Multi-tenancy/branch scoping is a future backend deliverable, so `School`/`Branch` are modeled but optional, and `useCurrentBranch` degrades gracefully to `null`.
- **No refresh-token endpoint exists.** A refresh-token storage slot and a `SessionContext.refresh()` stub are in place so the feature can be wired later without reshaping anything.

---

## 3. Files created

### Canonical domain types — `packages/core/src/types/`
| File | Contents |
| --- | --- |
| `api.ts` | `ApiResponse<T>`, `PaginatedResponse<T>`, `ApiError` |
| `permission.ts` | `Permission`, `PermissionName` |
| `role.ts` | `Role` (slug + display name + permissions) |
| `school.ts` | `School` (tenant; future-ready) |
| `branch.ts` | `Branch` (campus; future-ready) |
| `user.ts` | `User` (camelCase canonical model, `role: UserRole \| null`) |
| `auth.ts` | `LoginCredentials`, `LoginResult`, `AuthStatus`, `AppTarget`, raw `ApiUser` / `ApiLoginData` |
| `session.ts` | `PersistedSessionMeta` |
| `index.ts` | barrel |

### Config — `packages/core/src/config/`
| File | Contents |
| --- | --- |
| `env.ts` | `API_BASE_URL`, `API_TIMEOUT_MS` (from `expo.extra` → `EXPO_PUBLIC_*` → default) |

### Secure storage — `packages/core/src/storage/`
| File | Contents |
| --- | --- |
| `keys.ts` | SecureStore + AsyncStorage key constants |
| `secureStorage.ts` | token get/set/clear **+ refresh-token slot** (SecureStore, `WHEN_UNLOCKED_THIS_DEVICE_ONLY`) |
| `authStorage.ts` | cached user, session metadata, `touchSessionMeta`, `clearAuthData` (AsyncStorage) |
| `index.ts` | barrel |

### API layer — `packages/core/src/api/`
| File | Contents |
| --- | --- |
| `client.ts` | axios singleton: bearer injection, 422 flattening, `ApiError` normalization, 401 → `onUnauthorized` |
| `auth.api.ts` | `login`, `logout`, `getProfile` (scope-limited; no Google/OTP/reset) |
| `index.ts` | barrel |

### Auth domain — `packages/core/src/auth/`
| File | Contents |
| --- | --- |
| `roleUtils.ts` | `normalizeRole` (**explicit-deny** → `null` for unknown), `rolesForApp`, `canAccessApp` |
| `mapUser.ts` | `mapApiUser` (snake_case → canonical `User`) |
| `sessionPolicy.ts` | pure expiry: server expiry + absolute age + idle window; `parseExpiresAt` |
| `SessionContext.tsx` | **Session provider** — token/expiry lifecycle, hydrate-on-start, `setSession`/`clearSession`/`touch`/`refresh` |
| `AuthContext.tsx` | **Auth provider** — identity, `login`/`logout`/`refreshUser`, session restore, 401 + foreground handling |
| `hooks.ts` | `useCurrentUser`, `useCurrentRole`, `useCurrentBranch`, `useCanAccessApp` |
| `index.ts` | barrel |

### Design-system primitives — `packages/ui/src/primitives/`
| File | Contents |
| --- | --- |
| `Button.tsx` | themed `Button` (primary/secondary/ghost, loading state) |
| `TextField.tsx` | themed `TextField` (label, focus/error states) |
| `index.ts` | barrel |

### Admin auth feature — `apps/admin/src/features/auth/`
| File | Contents |
| --- | --- |
| `screens/LoginScreen.tsx` | identifier + password + remember-me, error banner, show/hide password |
| `screens/AuthLoadingScreen.tsx` | splash during session restore |
| `screens/AccessDeniedScreen.tsx` | wrong-app guard screen (Open Staff App / sign out) |
| `index.ts` | barrel |

### Documentation
- `docs/execution/batch-2-report.md` (this file)

---

## 4. Files modified

| File | Change |
| --- | --- |
| `packages/core/src/index.ts` | export new `config/env`, `types`, `storage`, `api`, `auth` barrels |
| `packages/core/src/config/roles.ts` | add `STAFF_APP_ROLES`, `isAdminAppRole`, `isStaffAppRole` |
| `packages/core/package.json` | declare peer deps (axios, expo-constants, expo-secure-store, async-storage, react, react-native); updated description |
| `packages/ui/src/index.ts` | export `primitives` |
| `apps/admin/App.tsx` | wrap tree in `SessionProvider` → `AuthProvider` |
| `apps/admin/src/navigation/AdminRootNavigator.tsx` | add `RootGate` route guard (4 auth states) |
| `apps/admin/src/navigation/DrawerContent.tsx` | footer with current user + sign-out (logout flow surfaced) |

---

## 5. How each required deliverable maps to code

| # | Requirement | Implementation |
| --- | --- | --- |
| 1 | Auth Context | `AuthContext.tsx` (`AuthProvider` / `useAuth`) |
| 2 | Session Context | `SessionContext.tsx` (`SessionProvider` / `useSession`) |
| 3 | Secure Token Storage | `storage/secureStorage.ts` (SecureStore, + refresh slot) |
| 4 | Login Screen | `features/auth/screens/LoginScreen.tsx` |
| 5 | Logout Flow | `AuthContext.logout` + drawer footer "Sign out" + 401 auto-logout |
| 6 | Protected Routes | `RootGate` in `AdminRootNavigator.tsx` |
| 7 | App Guards | `canAccessApp(user, 'admin')` + `RootGate` (see §6) |
| 8 | Current User Hook | `useCurrentUser` |
| 9 | Current Role Hook | `useCurrentRole` |
| 10 | Current Branch Hook | `useCurrentBranch` |
| — | Canonical types | `packages/core/src/types/{user,role,permission,school,branch}.ts` |

---

## 6. App-guard behavior

`RootGate` (`apps/admin/src/navigation/AdminRootNavigator.tsx`) resolves four states:

| State | Result |
| --- | --- |
| `initializing` | `AuthLoadingScreen` (splash while session restores) |
| `unauthenticated` | `LoginScreen` |
| authenticated, role ∉ `ADMIN_APP_ROLES` | `AccessDeniedScreen` |
| authenticated, role ∈ `ADMIN_APP_ROLES` | the app (`NavigationContainer` → `DrawerNavigator`) |

- **Teacher opening Admin App → Access Denied:** a teacher authenticates successfully (valid Sanctum token) but their normalized role is not in `ADMIN_APP_ROLES`, so `canAccessApp(user,'admin')` is `false` → `AccessDeniedScreen`.
- **Admin opening Staff App → Access Denied:** enforced symmetrically by the **Staff App** using the same shared helper `canAccessApp(user,'staff')`. The helper and `STAFF_APP_ROLES` live in `@erp/core` so the Staff App reuses them verbatim — no Admin-side code change is needed.
- **Unauthenticated → Login**, **Authenticated (authorized) → App** as above.

Login / loading / denied screens render **outside** `NavigationContainer` (they need no navigator), so deep links only resolve once the user is inside the app.

---

## 7. Architecture decisions

1. **Two separated contexts.** `SessionContext` owns the transport credential (token, expiry, persistence, validity, activity); `AuthContext` owns identity (user) and the login/logout orchestration on top of it. This matches the brief's distinct "Auth Context" and "Session Context" deliverables and keeps the token lifecycle independently testable.
2. **Explicit-deny role normalization (build plan §5.1).** `normalizeRole` returns `null` for unrecognized roles instead of defaulting to `teacher`. A `null` role fails every guard, so a misconfigured backend role can never silently inherit access.
3. **Canonical camelCase domain model + mapper.** The backend snake_case payload is converted once via `mapApiUser`; the rest of the app only ever sees the clean `User` type. Raw `ApiUser`/`ApiLoginData` shapes are isolated in `types/auth.ts`.
4. **Graceful expiry, three gates.** `isSessionExpired` checks server `expires_at`, an absolute 7-day ceiling, and an idle window (30 min normally / 7 days with remember-me). Checked on cold start and on app foreground.
5. **Offline-tolerant restore.** On launch with a valid token, a `GET /user` failure that is **not** a 401 (network/5xx) falls back to the cached profile so the app still opens offline; a true 401 tears the session down.
6. **Forward-compatible refresh tokens.** A SecureStore refresh-token slot and `SessionContext.refresh()` stub exist now; wiring them to a future `POST /token/refresh` requires no type or storage changes.
7. **`@erp/core` becomes the shared brain.** It now carries runtime deps (axios, SecureStore, AsyncStorage, expo-constants) declared as peer dependencies, resolved from the workspace root via the existing `module-resolver` + `tsconfig` path aliases — still consumed as source with no build step.
8. **Two new DS primitives, not a full kit.** Only `Button` and `TextField` were added to `@erp/ui` (what the login form needs); both are theme-driven and reusable.

---

## 8. Backend dependencies

- **Existing, relied upon (no change needed):** `POST /login`, `GET /user`, `POST /logout` (Sanctum). `login` already returns `expires_at` — consumed directly.
- **Future (modeled client-side, not yet available):**
  - **Multi-tenancy / branch scoping (Backlog E1/E2).** Backend should add `school`/`branch` (or `branches[]`) to the `/user` payload. `School`/`Branch` types and `useCurrentBranch` are ready; the mapper already reads optional `branches`, `branch_id`, `school_id`.
  - **Refresh-token endpoint.** No `POST /token/refresh` exists. Storage slot + `refresh()` stub are staged for it.
  - **Permission-first claims (build plan §7).** `permissions: string[]` is already captured on `User`; the RBAC engine that consumes them lands in a later batch.

---

## 9. Blockers

None. All targets typecheck under strict mode and the auth foundation integrates with the live Laravel endpoints. Branch-aware behavior is intentionally a no-op until the backend exposes tenant/branch data (tracked above as a backend dependency, not a blocker for this batch).
