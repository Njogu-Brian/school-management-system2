# Legacy app root

This `src/` tree is the **legacy single-binary** multi-role app.

**Do not add new features here.**

- Non-admin capture / self-service → [`apps/users`](../apps/users)
- Admin / management → [`apps/admin`](../apps/admin)
- Shared API / auth / UI → [`packages/core`](../packages/core), [`packages/ui`](../packages/ui)

See [`apps/users/CUTOVER.md`](../apps/users/CUTOVER.md).
