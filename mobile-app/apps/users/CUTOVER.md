# Legacy monorepo root (`mobile-app/src`) — cutover notes

## Policy

- **Do not ship new features** into root `mobile-app/src` (legacy single-binary multi-role app).
- New capture/self-service work lands in [`apps/users`](./).
- Admin/management work stays in [`apps/admin`](../admin/).

## Shared packages

- Prefer `@erp/core` API modules + query hooks.
- Prefer `@erp/ui` for all new screens (no React Native Paper in Users App).

## Remaining migrations from `src/api/*`

Move into `@erp/core` only when Users App needs them:

- `seniorTeacher.api.ts`
- `teacherRequirements.api.ts` (partially covered by `operations.api`)
- `feedback.api.ts`
- Any transport helpers not yet in `driverTransport.api` / `teacherTransport.api`

## Play / EAS

- Users App package: `com.royalkingsschools.users`
- Scheme: `royalkingsusers`
- Create a dedicated EAS project and replace the placeholder `EAS_PROJECT_ID` in `app.config.ts` before production builds.
- Store disclosures: biometrics, location (clock-in / future driver GPS).

## Admin cross-link

- Admin Access Denied → `royalkingsusers://`
- Users Access Denied → `royalkingsadmin://`
