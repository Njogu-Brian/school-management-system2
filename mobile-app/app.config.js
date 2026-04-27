// Embedded at native build time (EAS / gradlew). Use EXPO_PUBLIC_* only — do not use bare
// API_BASE_URL from .env here; it is often a dev/ngrok URL and breaks release APKs.
const apiBase =
  process.env.EXPO_PUBLIC_API_BASE_URL || 'https://erp.royalkingsschools.sc.ke/api';
const webBaseDefault =
  apiBase.replace(/\/api\/?$/i, '').replace(/\/$/, '') || 'https://erp.royalkingsschools.sc.ke';

/** EAS OTA: only "on" in EAS Build, never for local `expo start` (Expo Go) — see eas.json `env`. */
const wantsEasOta =
  process.env.EXPO_PUBLIC_UPDATES_ENABLED === '1' ||
  process.env.EXPO_PUBLIC_UPDATES_ENABLED === 'true';
const isEasBuild = process.env.EAS_BUILD === 'true' || process.env.EAS_BUILD === '1';
const updatesEnabledInThisBinary = wantsEasOta && isEasBuild;

/** Same as extra.eas.projectId — used by EAS Update (`eas update`). */
const EAS_PROJECT_ID = 'd8b53a3a-3093-407c-b552-de66fc1cc8bb';

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
    // OTA: local dev always off (EAS_BUILD unset). EAS release builds: set EXPO_PUBLIC_UPDATES_ENABLED
    // in eas.json + run `eas update` (same runtimeVersion) or you get download failures on device.
    updates: {
      url: `https://u.expo.dev/${EAS_PROJECT_ID}`,
      enabled: updatesEnabledInThisBinary,
      checkAutomatically:
        process.env.EXPO_PUBLIC_UPDATES_ON_LOAD === '1' ? 'ON_LOAD' : 'NEVER',
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
      'expo-web-browser',
      '@react-native-community/datetimepicker',
    ],
    extra: {
      eas: {
        projectId: EAS_PROJECT_ID,
      },
      API_BASE_URL: apiBase,
      API_TIMEOUT: process.env.API_TIMEOUT || process.env.EXPO_PUBLIC_API_TIMEOUT || '30000',
      /** Public web origin (no /api). Override when API is on a different host than the portal. */
      WEB_BASE_URL: process.env.EXPO_PUBLIC_WEB_BASE_URL || webBaseDefault,
      IOS_APP_STORE_ID: process.env.EXPO_PUBLIC_IOS_APP_STORE_ID || '',
      GOOGLE_ANDROID_CLIENT_ID: process.env.EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID || '',
      GOOGLE_IOS_CLIENT_ID: process.env.EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID || '',
      GOOGLE_WEB_CLIENT_ID: process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID || '',
    },
  },
};
