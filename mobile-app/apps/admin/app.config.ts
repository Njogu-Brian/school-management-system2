import type { ExpoConfig } from 'expo/config';

/**
 * Royal Kings Admin — Play Store release config.
 * Package name must match what you enter in Google Play Console (cannot change later).
 */
const apiBase = process.env.EXPO_PUBLIC_API_BASE_URL || 'https://erp.royalkingsschools.sc.ke/api';
const primaryColor = '#390754';

const config: ExpoConfig = {
  name: 'Royal Kings Admin',
  slug: 'royal-kings-admin',
  scheme: 'royalkingsadmin',
  version: '1.0.0',
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
  ios: {
    supportsTablet: true,
    bundleIdentifier: 'com.royalkingsschools.admin',
  },
  android: {
    package: 'com.royalkingsschools.admin',
    versionCode: 1,
    adaptiveIcon: {
      foregroundImage: './assets/adaptive-icon.png',
      backgroundColor: primaryColor,
    },
    permissions: ['USE_BIOMETRIC', 'USE_FINGERPRINT'],
  },
  plugins: ['expo-local-authentication'],
  extra: {
    API_BASE_URL: apiBase,
    eas: {
      projectId: process.env.EAS_PROJECT_ID,
    },
  },
};

export default config;
