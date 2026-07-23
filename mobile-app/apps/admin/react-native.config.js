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
};
