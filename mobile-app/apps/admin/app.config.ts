import type { ExpoConfig } from 'expo/config';

/**
 * Royal Kings Admin — Play Store release config.
 * Package name must match what you enter in Google Play Console (cannot change later).
 */
const apiBase = process.env.EXPO_PUBLIC_API_BASE_URL || 'https://erp.royalkingsschools.sc.ke/api';
const primaryColor = '#390754';
/** Linked EAS project: @briannjogu/royal-kings-admin */
const EAS_PROJECT_ID = '0d0b7844-fe28-441d-ab98-bb27890a38f3';
const APP_VERSION = '1.0.8';

const config: ExpoConfig = {
  name: 'Royal Kings Admin',
  slug: 'royal-kings-admin',
  scheme: 'royalkingsadmin',
  version: APP_VERSION,
  orientation: 'portrait',
  userInterfaceStyle: 'automatic',
  newArchEnabled: true,
  icon: './assets/icon.png',
  splash: {
    image: './assets/splash-icon.png',
    backgroundColor: primaryColor,
    resizeMode: 'contain',
  },
  assetBundlePatterns: ['**/*'],
  updates: {
    url: `https://u.expo.dev/${EAS_PROJECT_ID}`,
    // Disabled until the first OTA bundle is published — avoids a grey hang on cold start.
    enabled: false,
    checkAutomatically: 'NEVER',
    fallbackToCacheTimeout: 0,
  },
  runtimeVersion: APP_VERSION,
  ios: {
    supportsTablet: true,
    bundleIdentifier: 'com.royalkingsschools.admin',
  },
  android: {
    package: 'com.royalkingsschools.admin',
    versionCode: 9,
    softwareKeyboardLayoutMode: 'resize',
    adaptiveIcon: {
      foregroundImage: './assets/adaptive-icon.png',
      backgroundColor: primaryColor,
    },
    permissions: ['USE_BIOMETRIC', 'USE_FINGERPRINT'],
  },
  plugins: ['expo-local-authentication', 'expo-updates'],
  extra: {
    API_BASE_URL: apiBase,
    eas: {
      projectId: process.env.EAS_PROJECT_ID ?? EAS_PROJECT_ID,
    },
  },
};

export default config;
