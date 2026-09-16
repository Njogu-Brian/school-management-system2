/**
 * Admin app package id for Expo/RN autolinking (must match app.config.ts).
 * Overrides monorepo-root react-native.config.js (com.schoolerp).
 */
module.exports = {
  project: {
    android: {
      packageName: 'com.royalkingsschools.admin',
    },
  },
  // Monorepo root still lists these native modules; Admin does not use them
  // and their prebuilt .so files fail Play's 16 KB page-size check.
  dependencies: {
    'react-native-pdf': {
      platforms: { android: null, ios: null },
    },
    'react-native-sms-retriever': {
      platforms: { android: null, ios: null },
    },
  },
};
