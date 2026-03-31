// Embedded at native build time (EAS / gradlew). Use EXPO_PUBLIC_* only — do not use bare
// API_BASE_URL from .env here; it is often a dev/ngrok URL and breaks release APKs.
const apiBase =
  process.env.EXPO_PUBLIC_API_BASE_URL || 'https://erp.royalkingsschools.sc.ke/api';
const webBaseDefault =
  apiBase.replace(/\/api\/?$/i, '').replace(/\/$/, '') || 'https://erp.royalkingsschools.sc.ke';

export default {
  expo: {
    newArchEnabled: true,
    name: 'Royal Kings ERP',
    icon: './assets/royal-kings-icon-filled.png',
    slug: 'school-erp-mobile',
    version: '1.0.0',
    orientation: 'portrait',
    userInterfaceStyle: 'automatic',
    splash: {
      backgroundColor: '#004A99',
      resizeMode: 'contain',
    },
    assetBundlePatterns: ['assets/**/*'],
    updates: {
      enabled: true,
      checkAutomatically: 'ON_LOAD',
      fallbackToCacheTimeout: 0,
    },
    runtimeVersion: process.env.EXPO_RUNTIME_VERSION || '1.0.0',
    ios: {
      supportsTablet: true,
      bundleIdentifier: 'com.schoolerp',
    },
    android: {
      adaptiveIcon: {
        foregroundImage: './assets/royal-kings-icon-foreground.png',
        backgroundColor: '#004A99',
      },
      package: 'com.schoolerp',
    },
    plugins: [
      [
        'expo-build-properties',
        {
          android: {
            usesCleartextTraffic: false,
            enableMinifyInReleaseBuilds: true,
            enableShrinkResourcesInReleaseBuilds: true,
          },
        },
      ],
      'expo-font',
      [
        'expo-image-picker',
        {
          photosPermission:
            'Allow access to your photo library to upload student and staff photos.',
        },
      ],
      'expo-notifications',
      'expo-secure-store',
      'expo-updates',
    ],
    extra: {
      eas: {
        projectId: 'd8b53a3a-3093-407c-b552-de66fc1cc8bb',
      },
      API_BASE_URL: apiBase,
      API_TIMEOUT: process.env.API_TIMEOUT || process.env.EXPO_PUBLIC_API_TIMEOUT || '30000',
      /** Public web origin (no /api). Override when API is on a different host than the portal. */
      WEB_BASE_URL: process.env.EXPO_PUBLIC_WEB_BASE_URL || webBaseDefault,
      IOS_APP_STORE_ID: process.env.EXPO_PUBLIC_IOS_APP_STORE_ID || '',
    },
  },
};
