# Multi-school SaaS + iOS scale-out

Working branch: **`epic/saas-ios-scale`** (keep `main` stable for Royal Kings Play Store hotfixes).

## Branching

| Branch | Purpose |
|--------|---------|
| `main` | Production ERP + Android releases |
| `epic/saas-ios-scale` | Multi-school registry, school-code mobile flow, iOS EAS |
| Short-lived `feature/*` off the epic | Optional for large slices; merge back into the epic |

Do **not** land unfinished tenancy on `main` until migrate + seed + smoke tests pass on staging.

## Architecture (DB per school)

1. **Control plane registry** table `schools_registry` (currently hosted on the Royal Kings Laravel app until a dedicated portal host exists).
2. Mobile calls `GET /api/schools/resolve?code=XXXX` on the **control plane**.
3. App stores `{ code, api_base_url, branding }` and points Axios at that tenant.
4. Login/OTP/Google continue against the **tenant** API (unchanged Sanctum flow).

Royal Kings is tenant `#1` with code **`RKS001`**.

## Backend commands

```bash
php artisan migrate
php artisan db:seed --class=SchoolsRegistrySeeder
php artisan schools:register "Demo Academy" --api-base-url=https://demo.example.com/api
```

Resolve smoke test:

```bash
curl "https://erp.royalkingsschools.sc.ke/api/schools/resolve?code=RKS001"
```

## Mobile flags

| Env | Meaning |
|-----|---------|
| `EXPO_PUBLIC_CONTROL_PLANE_BASE_URL` | Host for `/schools/resolve` |
| `EXPO_PUBLIC_API_BASE_URL` | Legacy default tenant (auto-bind when school code not required) |
| `EXPO_PUBLIC_REQUIRE_SCHOOL_CODE` | `true` = force school-code screen (SaaS store builds) |

While `REQUIRE_SCHOOL_CODE` is `false`, existing Play users silently bind to `RKS001` so they are not locked out.

## iOS without a Mac

1. Enroll in [Apple Developer Program](https://developer.apple.com/programs/) ($99/yr).
2. Create App IDs / App Store Connect apps for:
   - `com.royalkingsschools.users`
   - `com.royalkingsschools.admin`
3. From Windows (in each app folder):

```bash
npx eas login
npx eas credentials   # let EAS manage iOS certs/profiles
npx eas build -p ios --profile preview
```

4. Distribute via **TestFlight** (`eas submit -p ios`) or internal build URL.
5. Test on a borrowed iPhone, school staff TestFlight testers, or a device cloud (BrowserStack, etc.).

Fill `submit.production.ios` in each `eas.json` (`appleId`, `ascAppId`, `appleTeamId`) after App Store Connect apps exist.

## Next steps

- [ ] Run migrate + `SchoolsRegistrySeeder` on production/staging
- [ ] Apple Developer enrollment + first EAS iOS preview build
- [ ] Super-admin portal UI for creating schools
- [ ] `scripts/provision-school.sh` for DB-per-tenant provisioning
- [ ] Flip `REQUIRE_SCHOOL_CODE=true` for generic store builds
- [ ] “Change school” control on login/settings
