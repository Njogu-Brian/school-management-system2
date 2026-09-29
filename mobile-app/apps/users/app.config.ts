import type { ExpoConfig } from 'expo/config';
import path from 'path';
// Plain JS — Expo evaluates app.config via require and cannot resolve .ts helpers.
// eslint-disable-next-line @typescript-eslint/no-require-imports
const { loadEnvFile } = require('../../scripts/loadEnvFile');

// Expo only auto-loads apps/<name>/.env; also pull monorepo mobile-app/.env.
loadEnvFile(path.resolve(__dirname, '../../.env'));
loadEnvFile(path.resolve(__dirname, '.env'));

/**
 * Royal Kings Users — Play Store release config.
 * Teachers, parents, students, drivers, and other non-admin staff.
 */
const apiBase = process.env.EXPO_PUBLIC_API_BASE_URL || 'https://erp.royalkingsschools.sc.ke/api';
const controlPlaneBase =
  process.env.EXPO_PUBLIC_CONTROL_PLANE_BASE_URL || apiBase;
const requireSchoolCode = process.env.EXPO_PUBLIC_REQUIRE_SCHOOL_CODE === 'true';
const googleMapsApiKey = process.env.EXPO_PUBLIC_GOOGLE_MAPS_API_KEY || '';
const googleAndroidClientId = process.env.EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID || '';
const googleIosClientId = process.env.EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID || '';
const googleWebClientId = process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID || '';
const primaryColor = '#004A99';
/** EAS project for Royal Kings Users (`@breysoms-team/royal-kings-users`). */
const EAS_PROJECT_ID = process.env.EAS_PROJECT_ID ?? 'f958de71-5153-4219-a112-0b1b503eefe7';
const APP_VERSION = '1.0.8';

const config: ExpoConfig = {
  name: 'Royal Kings Users',
  slug: 'royal-kings-users',
  scheme: 'royalkingsusers',
  owner: 'breysoms-team',
  version: APP_VERSION,
  orientation: 'default',
  userInterfaceStyle: 'automatic',
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
    config: {
      googleMapsApiKey,
    },
    infoPlist: {
      NSLocationWhenInUseUsageDescription:
        'Royal Kings Users needs your location to share the school bus position and mark child pickup or drop-off points.',
      NSLocationAlwaysAndWhenInUseUsageDescription:
        'Royal Kings Users needs your location in the background while a trip is in progress so parents can track the bus.',
      UIBackgroundModes: ['location'],
      NSFaceIDUsageDescription: 'Unlock Royal Kings Users with Face ID.',
      NSPhotoLibraryUsageDescription: 'Allow Royal Kings Users to update your profile photo.',
      NSCameraUsageDescription: 'Allow Royal Kings Users to take a profile photo.',
    },
  },
  android: {
    package: 'com.royalkingsschools.users',
    versionCode: 18,
    softwareKeyboardLayoutMode: 'resize',
    adaptiveIcon: {
      foregroundImage: './assets/adaptive-icon.png',
      backgroundColor: primaryColor,
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
      'ACCESS_BACKGROUND_LOCATION',
      'FOREGROUND_SERVICE',
      'FOREGROUND_SERVICE_LOCATION',
    ],
  },
  plugins: [
    '../../plugins/withAndroid16KbPageSize',
    '../../plugins/withAndroidTabletSupport',
    'expo-local-authentication',
    'expo-updates',
    [
      'expo-location',
      {
        locationAlwaysAndWhenInUsePermission:
          'Allow Royal Kings Users to share the bus location while a trip is in progress.',
        isAndroidBackgroundLocationEnabled: true,
        isAndroidForegroundServiceEnabled: true,
      },
    ],
    'expo-task-manager',
    [
      'react-native-maps',
      {
        // No Cloud Map ID — keeps Maps SDK unlimited / free.
      },
    ],
    [
      'expo-image-picker',
      {
        photosPermission: 'Allow Royal Kings Users to update your profile photo.',
      },
    ],
    '@react-native-community/datetimepicker',
    'expo-secure-store',
    'expo-sharing',
    'expo-status-bar',
    'expo-web-browser',
  ],
  extra: {
    API_BASE_URL: apiBase,
    CONTROL_PLANE_BASE_URL: controlPlaneBase,
    REQUIRE_SCHOOL_CODE: requireSchoolCode,
    APP_SURFACE: 'users',
    GOOGLE_MAPS_API_KEY: googleMapsApiKey,
    GOOGLE_ANDROID_CLIENT_ID: googleAndroidClientId,
    GOOGLE_IOS_CLIENT_ID: googleIosClientId,
    GOOGLE_WEB_CLIENT_ID: googleWebClientId,
    eas: {
      projectId: EAS_PROJECT_ID,
    },
  },
};

export default config;
