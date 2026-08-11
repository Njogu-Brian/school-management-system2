import Constants from 'expo-constants';

/**
 * Runtime environment. Reads from the app's `expo.extra` (set in `app.config.ts`),
 * falling back to `EXPO_PUBLIC_*` and finally a production default.
 */
const extra = Constants.expoConfig?.extra as Record<string, string | boolean | undefined> | undefined;

const DEFAULT_API_BASE_URL = 'https://erp.royalkingsschools.sc.ke/api';

/** Build-time / fallback tenant API (used until a school code is resolved). */
export const DEFAULT_TENANT_API_BASE_URL =
  (typeof extra?.API_BASE_URL === 'string' && extra.API_BASE_URL) ||
  process.env.EXPO_PUBLIC_API_BASE_URL ||
  DEFAULT_API_BASE_URL;

/**
 * Control-plane host for school-code resolve.
 * Until a dedicated portal host exists, this is the same as the legacy ERP.
 */
export const CONTROL_PLANE_BASE_URL =
  (typeof extra?.CONTROL_PLANE_BASE_URL === 'string' && extra.CONTROL_PLANE_BASE_URL) ||
  process.env.EXPO_PUBLIC_CONTROL_PLANE_BASE_URL ||
  DEFAULT_TENANT_API_BASE_URL;

/**
 * When true, first launch requires entering a school code (no silent Royal Kings bind).
 * Keep false for Play Store continuity; flip true for generic SaaS store builds.
 */
export const REQUIRE_SCHOOL_CODE =
  extra?.REQUIRE_SCHOOL_CODE === true ||
  extra?.REQUIRE_SCHOOL_CODE === 'true' ||
  process.env.EXPO_PUBLIC_REQUIRE_SCHOOL_CODE === 'true';

/** @deprecated Prefer getApiBaseUrl() / school context — kept for call sites during migration. */
export const API_BASE_URL = DEFAULT_TENANT_API_BASE_URL;

export const API_TIMEOUT_MS = Number(
  (typeof extra?.API_TIMEOUT === 'string' && extra.API_TIMEOUT) ||
    process.env.EXPO_PUBLIC_API_TIMEOUT ||
    '30000',
);

export const GOOGLE_ANDROID_CLIENT_ID =
  (typeof extra?.GOOGLE_ANDROID_CLIENT_ID === 'string' && extra.GOOGLE_ANDROID_CLIENT_ID) ||
  process.env.EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID ||
  '';

export const GOOGLE_IOS_CLIENT_ID =
  (typeof extra?.GOOGLE_IOS_CLIENT_ID === 'string' && extra.GOOGLE_IOS_CLIENT_ID) ||
  process.env.EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID ||
  '';

export const GOOGLE_WEB_CLIENT_ID =
  (typeof extra?.GOOGLE_WEB_CLIENT_ID === 'string' && extra.GOOGLE_WEB_CLIENT_ID) ||
  process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID ||
  '';

/** Whether Google OAuth client IDs are configured for the current platform. */
export function hasGoogleOAuthConfig(platformOs: string): boolean {
  if (platformOs === 'android') {
    return Boolean(GOOGLE_ANDROID_CLIENT_ID);
  }
  if (platformOs === 'ios') {
    return Boolean(GOOGLE_IOS_CLIENT_ID);
  }
  return Boolean(GOOGLE_WEB_CLIENT_ID);
}

/** Legacy Royal Kings entry used when REQUIRE_SCHOOL_CODE is false. */
export const LEGACY_ANCHOR_SCHOOL = {
  code: 'RKS001',
  name: 'Royal Kings Schools',
  slug: 'royal-kings',
  apiBaseUrl: DEFAULT_TENANT_API_BASE_URL,
  status: 'active' as const,
  branding: {
    logoUrl: null as string | null,
    primaryColor: '#004A99',
  },
};
