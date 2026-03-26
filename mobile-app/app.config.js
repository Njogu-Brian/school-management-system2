const apiBase =
  process.env.EXPO_PUBLIC_API_BASE_URL ||
  process.env.API_BASE_URL ||
  'http://localhost:8000/api';
const webBaseDefault = apiBase.replace(/\/api\/?$/i, '').replace(/\/$/, '') || 'http://localhost:8000';

export default {
  expo: {
    newArchEnabled: true,
    name: 'School ERP',
    slug: 'school-erp-mobile',
    version: '1.0.0',
    orientation: 'portrait',
    userInterfaceStyle: 'automatic',
    splash: {
      backgroundColor: '#004A99',
      resizeMode: 'contain',
    },
    assetBundlePatterns: ['**/*'],
    updates: {
      enabled: false,
    },
    ios: {
      supportsTablet: true,
      bundleIdentifier: 'com.schoolerp',
    },
    android: {
      adaptiveIcon: {
        backgroundColor: '#004A99',
      },
      package: 'com.schoolerp',
    },
    plugins: [
      ['expo-build-properties', { android: { usesCleartextTraffic: true } }],
      'expo-font',
      [
        'expo-image-picker',
        {
          photosPermission:
            'Allow access to your photo library to upload student and staff photos.',
        },
      ],
      'expo-notifications',
    ],
    extra: {
      eas: {
        projectId: 'd8b53a3a-3093-407c-b552-de66fc1cc8bb',
      },
      API_BASE_URL: apiBase,
      API_TIMEOUT: process.env.API_TIMEOUT || process.env.EXPO_PUBLIC_API_TIMEOUT || '30000',
      /** Public web origin (no /api). Override when API is on a different host than the portal. */
      WEB_BASE_URL: process.env.EXPO_PUBLIC_WEB_BASE_URL || webBaseDefault,
    },
  },
};
