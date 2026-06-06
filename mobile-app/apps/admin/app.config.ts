import type { ExpoConfig } from 'expo/config';

/**
 * Admin App Expo config (build plan §1.4: separate binary `com.schoolerp.admin`).
 *
 * Kept intentionally minimal for the shell batch. A dedicated EAS project id, OTA
 * channels, branding assets, and push config are added in the release batch (Sprint 1+).
 */
const apiBase = process.env.EXPO_PUBLIC_API_BASE_URL || 'https://erp.royalkingsschools.sc.ke/api';

const googleAndroid = process.env.EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID ?? '';
const googleIos = process.env.EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID ?? '';
const googleWeb = process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID ?? '';

const config: ExpoConfig = {
  name: 'School ERP Admin',
  slug: 'school-erp-admin',
  scheme: 'schoolerpadmin',
  version: '1.0.0',
  orientation: 'portrait',
  userInterfaceStyle: 'automatic',
  newArchEnabled: true,
  splash: {
    backgroundColor: '#004A99',
    resizeMode: 'contain',
  },
  assetBundlePatterns: ['**/*'],
  ios: {
    supportsTablet: true,
    bundleIdentifier: 'com.schoolerp.admin',
  },
  android: {
    package: 'com.schoolerp.admin',
    adaptiveIcon: {
      backgroundColor: '#004A99',
    },
  },
  plugins: ['expo-local-authentication', 'expo-web-browser'],
  extra: {
    API_BASE_URL: apiBase,
    GOOGLE_ANDROID_CLIENT_ID: googleAndroid,
    GOOGLE_IOS_CLIENT_ID: googleIos,
    GOOGLE_WEB_CLIENT_ID: googleWeb,
  },
};

export default config;
