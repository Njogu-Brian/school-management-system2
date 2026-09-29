import type { ExpoConfig } from 'expo/config';
import path from 'path';
// Plain JS — Expo evaluates app.config via require and cannot resolve .ts helpers.
// eslint-disable-next-line @typescript-eslint/no-require-imports
const { loadEnvFile } = require('../../scripts/loadEnvFile');

loadEnvFile(path.resolve(__dirname, '../../.env'));
loadEnvFile(path.resolve(__dirname, '.env'));

/**
 * Edulynk — one Android + iOS binary for every school.
 * First launch asks for a school code; tenant /app-branding then applies name and colours.
 * OS icon and home-screen name stay Edulynk (store limitation).
 */
const apiBase = process.env.EXPO_PUBLIC_API_BASE_URL || 'https://erp.royalkingsschools.sc.ke/api';
const controlPlaneBase =
  process.env.EXPO_PUBLIC_CONTROL_PLANE_BASE_URL || apiBase;
const requireSchoolCode = process.env.EXPO_PUBLIC_REQUIRE_SCHOOL_CODE !== 'false';
const productWebsite = process.env.EXPO_PUBLIC_PRODUCT_WEBSITE_URL || 'https://edulynk.co.ke';
const googleMapsApiKey = process.env.EXPO_PUBLIC_GOOGLE_MAPS_API_KEY || '';
const googleAndroidClientId = process.env.EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID || '';
const googleIosClientId = process.env.EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID || '';
const googleWebClientId = process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID || '';
const splashBackground = '#000000';
/** Linked EAS project under @breysoms-team. */
const EAS_PROJECT_ID = process.env.EAS_PROJECT_ID ?? '54d4662d-a1f2-472d-86de-acfec0eea76d';
const APP_VERSION = '1.0.1';

const updatesUrl = `https://u.expo.dev/${EAS_PROJECT_ID}`;

const config: ExpoConfig = {
  name: 'Edulynk',
  slug: 'edulynk',
  scheme: 'edulynk',
  owner: 'breysoms-team',
  version: APP_VERSION,
  orientation: 'default',
  userInterfaceStyle: 'automatic',
  icon: './assets/icon.png',
  splash: {
    image: './assets/splash-icon.png',
    backgroundColor: splashBackground,
    resizeMode: 'contain',
  },
  assetBundlePatterns: ['**/*'],
  updates: {
    url: updatesUrl,
    enabled: true,
    checkAutomatically: 'ON_LOAD',
    fallbackToCacheTimeout: 0,
  },
  runtimeVersion: APP_VERSION,
  ios: {
    supportsTablet: true,
    requireFullScreen: false,
    bundleIdentifier: 'com.edulynk.app',
    buildNumber: '1',
    config: {
      googleMapsApiKey,
    },
    infoPlist: {
      UIRequiresFullScreen: false,
      NSLocationWhenInUseUsageDescription:
        'Edulynk uses your location for staff clock-in and school bus tracking when your school enables them.',
      NSLocationAlwaysAndWhenInUseUsageDescription:
        'Edulynk needs background location while a trip is in progress so parents can track the bus.',
      UIBackgroundModes: ['location'],
      NSFaceIDUsageDescription: 'Unlock Edulynk with Face ID.',
      NSPhotoLibraryUsageDescription: 'Allow Edulynk to update profile photos.',
      NSCameraUsageDescription: 'Allow Edulynk to take a profile photo.',
      ITSAppUsesNonExemptEncryption: false,
    },
  },
  android: {
    package: 'com.edulynk.app',
    versionCode: 2,
    softwareKeyboardLayoutMode: 'resize',
    adaptiveIcon: {
      foregroundImage: './assets/adaptive-icon.png',
      backgroundColor: '#000000',
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
          'Allow Edulynk to share the bus location while a trip is in progress.',
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
        photosPermission: 'Allow Edulynk to update your profile photo.',
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
    PRODUCT_WEBSITE_URL: productWebsite,
    APP_SURFACE: 'combined',
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
