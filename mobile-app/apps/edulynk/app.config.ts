import type { ExpoConfig } from 'expo/config';

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
const splashBackground = '#000000';
/** Linked EAS project — filled by `eas init` / extra.eas.projectId. */
const EAS_PROJECT_ID = process.env.EAS_PROJECT_ID ?? '53f1ee05-d923-4583-b2d2-f38b118c1341';
const APP_VERSION = '1.0.1';

const updatesUrl = `https://u.expo.dev/${EAS_PROJECT_ID}`;

const config: ExpoConfig = {
  name: 'Edulynk',
  slug: 'edulynk',
  scheme: 'edulynk',
  owner: 'briannjogu',
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
    infoPlist: {
      UIRequiresFullScreen: false,
      NSLocationWhenInUseUsageDescription:
        'Edulynk uses your location for staff clock-in and school transport features when your school enables them.',
      NSLocationAlwaysAndWhenInUseUsageDescription:
        'Edulynk uses your location for staff clock-in and school transport features when your school enables them.',
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
    permissions: [
      'USE_BIOMETRIC',
      'USE_FINGERPRINT',
      'ACCESS_COARSE_LOCATION',
      'ACCESS_FINE_LOCATION',
    ],
  },
  plugins: [
    '../../plugins/withAndroid16KbPageSize',
    '../../plugins/withAndroidTabletSupport',
    'expo-local-authentication',
    'expo-updates',
    'expo-location',
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
    eas: {
      projectId: EAS_PROJECT_ID,
    },
  },
};

export default config;
