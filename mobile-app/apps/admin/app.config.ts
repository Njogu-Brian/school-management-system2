import type { ExpoConfig } from 'expo/config';

/**
 * Royal Kings Admin — Play Store release config.
 * Package name must match what you enter in Google Play Console (cannot change later).
 */
const apiBase = process.env.EXPO_PUBLIC_API_BASE_URL || 'https://erp.royalkingsschools.sc.ke/api';
/** Linked EAS project: @briannjogu/royal-kings-admin */
const EAS_PROJECT_ID = '0d0b7844-fe28-441d-ab98-bb27890a38f3';
const APP_VERSION = '1.0.11';
/** Matches Royal Kings logo purple used in launcher assets. */
const iconBackground = '#390754';

const config: ExpoConfig = {
  name: 'Royal Kings Admin',
  slug: 'royal-kings-admin',
  scheme: 'royalkingsadmin',
  version: APP_VERSION,
  orientation: 'portrait',
  userInterfaceStyle: 'automatic',
  // Bridgeless/new-arch left on; NetInfo is guarded in JS if native link is missing.
  newArchEnabled: true,
  icon: './assets/icon.png',
  splash: {
    image: './assets/splash-icon.png',
    backgroundColor: iconBackground,
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
    versionCode: 12,
    softwareKeyboardLayoutMode: 'resize',
    adaptiveIcon: {
      foregroundImage: './assets/adaptive-icon.png',
      backgroundColor: iconBackground,
    },
    permissions: ['USE_BIOMETRIC', 'USE_FINGERPRINT'],
  },
  plugins: ['expo-local-authentication', 'expo-image-picker', 'expo-updates'],
  experiments: {
    // Keep Metro resolution aligned with native autolinking in the monorepo.
    autolinkingModuleResolution: true,
  },
  extra: {
    API_BASE_URL: apiBase,
    eas: {
      projectId: process.env.EAS_PROJECT_ID ?? EAS_PROJECT_ID,
    },
  },
};

export default config;
