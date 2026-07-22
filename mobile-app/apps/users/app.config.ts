import type { ExpoConfig } from 'expo/config';

/**
 * Royal Kings Users — Play Store release config.
 * Teachers, parents, students, drivers, and other non-admin staff.
 */
const apiBase = process.env.EXPO_PUBLIC_API_BASE_URL || 'https://erp.royalkingsschools.sc.ke/api';
const primaryColor = '#004A99';
/** Placeholder until a dedicated EAS project is linked. */
const EAS_PROJECT_ID = process.env.EAS_PROJECT_ID ?? '00000000-0000-0000-0000-000000000000';
const APP_VERSION = '1.0.0';

const config: ExpoConfig = {
  name: 'Royal Kings Users',
  slug: 'royal-kings-users',
  scheme: 'royalkingsusers',
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
    enabled: false,
    checkAutomatically: 'NEVER',
    fallbackToCacheTimeout: 0,
  },
  runtimeVersion: APP_VERSION,
  ios: {
    supportsTablet: true,
    bundleIdentifier: 'com.royalkingsschools.users',
  },
  android: {
    package: 'com.royalkingsschools.users',
    versionCode: 1,
    softwareKeyboardLayoutMode: 'resize',
    adaptiveIcon: {
      foregroundImage: './assets/adaptive-icon.png',
      backgroundColor: primaryColor,
    },
    permissions: ['USE_BIOMETRIC', 'USE_FINGERPRINT', 'ACCESS_COARSE_LOCATION', 'ACCESS_FINE_LOCATION'],
  },
  plugins: ['expo-local-authentication', 'expo-updates', 'expo-location'],
  extra: {
    API_BASE_URL: apiBase,
    eas: {
      projectId: process.env.EAS_PROJECT_ID ?? EAS_PROJECT_ID,
    },
  },
};

export default config;
