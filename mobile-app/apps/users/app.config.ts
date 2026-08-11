import type { ExpoConfig } from 'expo/config';

/**
 * Royal Kings Users — Play Store release config.
 * Teachers, parents, students, drivers, and other non-admin staff.
 */
const apiBase = process.env.EXPO_PUBLIC_API_BASE_URL || 'https://erp.royalkingsschools.sc.ke/api';
const controlPlaneBase =
  process.env.EXPO_PUBLIC_CONTROL_PLANE_BASE_URL || apiBase;
const requireSchoolCode = process.env.EXPO_PUBLIC_REQUIRE_SCHOOL_CODE === 'true';
const primaryColor = '#004A99';
/** EAS project for Royal Kings Users (`@briannjogu/royal-kings-users`). */
const EAS_PROJECT_ID = process.env.EAS_PROJECT_ID ?? '9655dc56-ce2d-4a0b-b7e0-57460abbac8d';
const APP_VERSION = '1.0.2';

const config: ExpoConfig = {
  name: 'Royal Kings Users',
  slug: 'royal-kings-users',
  scheme: 'royalkingsusers',
  version: APP_VERSION,
  orientation: 'default',
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
    enabled: true,
    checkAutomatically: 'ON_LOAD',
    fallbackToCacheTimeout: 0,
  },
  runtimeVersion: APP_VERSION,
  ios: {
    supportsTablet: true,
    bundleIdentifier: 'com.royalkingsschools.users',
    buildNumber: '1',
    infoPlist: {
      NSLocationWhenInUseUsageDescription:
        'Royal Kings Users needs your location to show live school bus tracking.',
      NSLocationAlwaysAndWhenInUseUsageDescription:
        'Royal Kings Users needs your location to show live school bus tracking.',
      NSFaceIDUsageDescription: 'Unlock Royal Kings Users with Face ID.',
      NSPhotoLibraryUsageDescription: 'Allow Royal Kings Users to update your profile photo.',
      NSCameraUsageDescription: 'Allow Royal Kings Users to take a profile photo.',
    },
  },
  android: {
    package: 'com.royalkingsschools.users',
    versionCode: 5,
    softwareKeyboardLayoutMode: 'resize',
    adaptiveIcon: {
      foregroundImage: './assets/adaptive-icon.png',
      backgroundColor: primaryColor,
    },
    permissions: ['USE_BIOMETRIC', 'USE_FINGERPRINT', 'ACCESS_COARSE_LOCATION', 'ACCESS_FINE_LOCATION'],
  },
  plugins: [
    'expo-local-authentication',
    'expo-updates',
    'expo-location',
    [
      'expo-image-picker',
      {
        photosPermission: 'Allow Royal Kings Users to update your profile photo.',
      },
    ],
  ],
  extra: {
    API_BASE_URL: apiBase,
    CONTROL_PLANE_BASE_URL: controlPlaneBase,
    REQUIRE_SCHOOL_CODE: requireSchoolCode,
    eas: {
      projectId: EAS_PROJECT_ID,
    },
  },
};

export default config;
