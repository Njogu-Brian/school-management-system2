# School ERP Mobile (monorepo)

Supported apps only:

| App | Package | Audience |
|-----|---------|----------|
| **Admin** | `@erp/admin` (`apps/admin`) | School management / RBAC admin |
| **Users** | `@erp/users` (`apps/users`) | Teachers, parents, students, drivers |

Shared code lives in `packages/core` and `packages/ui`.

The legacy single-app monolith (`com.schoolerp` / root `src/`) has been removed.

## Getting started

```bash
cd mobile-app
npm install
```

Copy each app's `.env.example` to `.env` and set `API_BASE_URL` / Expo public API URL as needed.

### Run Admin

```bash
npm run start:admin
# or
npm run android:admin
```

### Run Users

```bash
npm run start:users
# or
npm run android:users
```

### EAS builds

Use each app's own `eas.json` from its directory:

```bash
cd apps/admin && eas build --platform android --profile preview
cd apps/users && eas build --platform android --profile preview
```

## Structure

```
mobile-app/
├── apps/
│   ├── admin/       # Admin Expo app
│   └── users/       # Users Expo app
├── packages/
│   ├── core/        # API client, auth, shared logic
│   └── ui/          # Design system components
├── package.json     # Workspaces root (not a runnable app)
└── README.md
```
