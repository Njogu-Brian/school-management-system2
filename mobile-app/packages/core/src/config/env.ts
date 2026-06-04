import Constants from 'expo-constants';

/**
 * Runtime environment. Reads from the app's `expo.extra` (set in `app.config.ts`),
 * falling back to `EXPO_PUBLIC_*` and finally a production default.
 */
const extra = Constants.expoConfig?.extra as Record<string, string> | undefined;

const DEFAULT_API_BASE_URL = 'https://erp.royalkingsschools.sc.ke/api';

export const API_BASE_URL =
  extra?.API_BASE_URL ?? process.env.EXPO_PUBLIC_API_BASE_URL ?? DEFAULT_API_BASE_URL;

export const API_TIMEOUT_MS = Number(
  extra?.API_TIMEOUT ?? process.env.EXPO_PUBLIC_API_TIMEOUT ?? '30000',
);

export const GOOGLE_ANDROID_CLIENT_ID =
  extra?.GOOGLE_ANDROID_CLIENT_ID ?? process.env.EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID ?? '';

export const GOOGLE_IOS_CLIENT_ID =
  extra?.GOOGLE_IOS_CLIENT_ID ?? process.env.EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID ?? '';

export const GOOGLE_WEB_CLIENT_ID =
  extra?.GOOGLE_WEB_CLIENT_ID ?? process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID ?? '';

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
