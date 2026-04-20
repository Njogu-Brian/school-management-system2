/**
 * Environment variables - works with Expo and react-native-dotenv
 */
import Constants from 'expo-constants';

const DEFAULT_API_BASE_URL = 'https://erp.royalkingsschools.sc.ke/api';
const DEFAULT_WEB_BASE_URL = 'https://erp.royalkingsschools.sc.ke';

const extra = Constants.expoConfig?.extra as Record<string, string> | undefined;
export const API_BASE_URL =
    extra?.API_BASE_URL ?? process.env.EXPO_PUBLIC_API_BASE_URL ?? DEFAULT_API_BASE_URL;
export const API_TIMEOUT = extra?.API_TIMEOUT ?? process.env.EXPO_PUBLIC_API_TIMEOUT ?? '30000';

/** Web origin for public links (M-Pesa waiting page, payment link). Strips trailing `/api`. */
export function getWebBaseUrl(): string {
    const raw = extra?.WEB_BASE_URL ?? process.env.EXPO_PUBLIC_WEB_BASE_URL ?? API_BASE_URL;
    return raw.replace(/\/api\/?$/i, '').replace(/\/$/, '') || DEFAULT_WEB_BASE_URL;
}

/**
 * Public web origin (no `/api`). Set `EXPO_PUBLIC_WEB_BASE_URL` when the portal URL differs from the API host.
 * Falls back to stripping `/api` from `API_BASE_URL`.
 */
export const WEB_BASE_URL =
    extra?.WEB_BASE_URL ?? process.env.EXPO_PUBLIC_WEB_BASE_URL ?? getWebBaseUrl();

export const GOOGLE_ANDROID_CLIENT_ID =
    extra?.GOOGLE_ANDROID_CLIENT_ID ?? process.env.EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID ?? '';
export const GOOGLE_IOS_CLIENT_ID =
    extra?.GOOGLE_IOS_CLIENT_ID ?? process.env.EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID ?? '';
export const GOOGLE_WEB_CLIENT_ID =
    extra?.GOOGLE_WEB_CLIENT_ID ?? process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID ?? '';
