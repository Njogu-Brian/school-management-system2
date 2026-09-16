/**
 * Users app package id for Expo/RN autolinking (must match app.config.ts).
 */
module.exports = {
  project: {
    android: {
      packageName: 'com.royalkingsschools.users',
    },
  },
  dependencies: {
    'react-native-pdf': {
      platforms: { android: null, ios: null },
    },
    'react-native-sms-retriever': {
      platforms: { android: null, ios: null },
    },
  },
};
