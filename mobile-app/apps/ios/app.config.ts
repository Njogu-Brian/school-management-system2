import type { ExpoConfig } from 'expo/config';

/**
 * Royal Kings — combined iOS / iPadOS app.
 * One binary: admin workspaces + teacher/parent/student/driver shells, with Work | Home.
 */
const apiBase = process.env.EXPO_PUBLIC_API_BASE_URL || 'https://erp.royalkingsschools.sc.ke/api';
const controlPlaneBase =
  process.env.EXPO_PUBLIC_CONTROL_PLANE_BASE_URL || apiBase;
const requireSchoolCode = process.env.EXPO_PUBLIC_REQUIRE_SCHOOL_CODE === 'true';
const primaryColor = '#004A99';
const APP_VERSION = '1.0.0';

const config: ExpoConfig = {
  name: 'Royal Kings',
  slug: 'royal-kings',
  scheme: 'royalkings',
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
  ios: {
    supportsTablet: true,
    requireFullScreen: false,
    bundleIdentifier: 'com.royalkingsschools.app',
    buildNumber: '1',
    infoPlist: {
      UIRequiresFullScreen: false,
      NSLocationWhenInUseUsageDescription:
        'Royal Kings needs your location to show live school bus tracking.',
      NSLocationAlwaysAndWhenInUseUsageDescription:
        'Royal Kings needs your location to show live school bus tracking.',
      NSFaceIDUsageDescription: 'Unlock Royal Kings with Face ID.',
      NSPhotoLibraryUsageDescription: 'Allow Royal Kings to update profile photos.',
      NSCameraUsageDescription: 'Allow Royal Kings to take a profile photo.',
    },
  },
  android: {
    package: 'com.royalkingsschools.app',
    adaptiveIcon: {
      foregroundImage: './assets/adaptive-icon.png',
      backgroundColor: primaryColor,
    },
  },
  plugins: [
    'expo-local-authentication',
    'expo-location',
    [
      'expo-image-picker',
      {
        photosPermission: 'Allow Royal Kings to update your profile photo.',
      },
    ],
  ],
  extra: {
    API_BASE_URL: apiBase,
    CONTROL_PLANE_BASE_URL: controlPlaneBase,
    REQUIRE_SCHOOL_CODE: requireSchoolCode,
    APP_SURFACE: 'combined',
  },
};

export default config;
