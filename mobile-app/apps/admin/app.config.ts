import type { ExpoConfig } from 'expo/config';
import path from 'path';
// Plain JS — Expo evaluates app.config via require and cannot resolve .ts helpers.
// eslint-disable-next-line @typescript-eslint/no-require-imports
const { loadEnvFile } = require('../../scripts/loadEnvFile');

loadEnvFile(path.resolve(__dirname, '../../.env'));
loadEnvFile(path.resolve(__dirname, '.env'));

/**
 * Royal Kings Admin — Play Store release config.
 * Package name must match what you enter in Google Play Console (cannot change later).
 */
const apiBase = process.env.EXPO_PUBLIC_API_BASE_URL || 'https://erp.royalkingsschools.sc.ke/api';
const controlPlaneBase =
  process.env.EXPO_PUBLIC_CONTROL_PLANE_BASE_URL || apiBase;
const requireSchoolCode = process.env.EXPO_PUBLIC_REQUIRE_SCHOOL_CODE === 'true';
const googleMapsApiKey = process.env.EXPO_PUBLIC_GOOGLE_MAPS_API_KEY || '';
const googleAndroidClientId = process.env.EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID || '';
const googleIosClientId = process.env.EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID || '';
const googleWebClientId = process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID || '';
/** Linked EAS project: @breysoms-team/royal-kings-admin */
const EAS_PROJECT_ID = process.env.EAS_PROJECT_ID ?? 'c647e9a8-c6a0-4a8a-964e-381032e4bd9c';
const APP_VERSION = '1.0.17';
/** Matches Royal Kings logo purple used in launcher assets. */
const iconBackground = '#390754';

const config: ExpoConfig = {
  name: 'Royal Kings Admin',
  slug: 'royal-kings-admin',
  scheme: 'royalkingsadmin',
  owner: 'breysoms-team',
  version: APP_VERSION,
  orientation: 'default',
  userInterfaceStyle: 'automatic',
  icon: './assets/icon.png',
  splash: {
    image: './assets/splash-icon.png',
    backgroundColor: iconBackground,
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
    bundleIdentifier: 'com.royalkingsschools.admin',
    buildNumber: '1',
    config: {
      googleMapsApiKey,
    },
    infoPlist: {
      NSLocationWhenInUseUsageDescription:
        'Royal Kings Admin needs your location to mark child pickup or drop-off points on the map.',
      NSFaceIDUsageDescription: 'Unlock Royal Kings Admin with Face ID.',
      NSPhotoLibraryUsageDescription: 'Allow Royal Kings Admin to update profile photos.',
      NSCameraUsageDescription: 'Allow Royal Kings Admin to take a profile photo.',
    },
  },
  android: {
    package: 'com.royalkingsschools.admin',
    versionCode: 20,
    softwareKeyboardLayoutMode: 'resize',
    adaptiveIcon: {
      foregroundImage: './assets/adaptive-icon.png',
      backgroundColor: iconBackground,
    },
    config: {
      googleMaps: {
        apiKey: googleMapsApiKey,
      },
    },
    permissions: [
      'USE_BIOMETRIC',
      'USE_FINGERPRINT',
      'ACCESS_COARSE_LOCATION',
      'ACCESS_FINE_LOCATION',
    ],
  },
  plugins: [
    '../../plugins/withAndroid16KbPageSize',
    'expo-local-authentication',
    'expo-image-picker',
    'expo-updates',
    'expo-location',
    [
      'react-native-maps',
      {
        // No Cloud Map ID — keeps Maps SDK unlimited / free.
      },
    ],
    '../../plugins/withAndroidTabletSupport',
    '@react-native-community/datetimepicker',
    'expo-secure-store',
    'expo-sharing',
    'expo-status-bar',
    'expo-web-browser',
  ],
  experiments: {
    // Keep Metro resolution aligned with native autolinking in the monorepo.
    autolinkingModuleResolution: true,
  },
  extra: {
    API_BASE_URL: apiBase,
    CONTROL_PLANE_BASE_URL: controlPlaneBase,
    REQUIRE_SCHOOL_CODE: requireSchoolCode,
    APP_SURFACE: 'admin',
    GOOGLE_MAPS_API_KEY: googleMapsApiKey,
    GOOGLE_ANDROID_CLIENT_ID: googleAndroidClientId,
    GOOGLE_IOS_CLIENT_ID: googleIosClientId,
    GOOGLE_WEB_CLIENT_ID: googleWebClientId,
    eas: {
      projectId: process.env.EAS_PROJECT_ID ?? EAS_PROJECT_ID,
    },
  },
};

export default config;
