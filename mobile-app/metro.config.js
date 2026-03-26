const { getDefaultConfig } = require('expo/metro-config');

/**
 * Metro for Expo SDK 54 — must extend expo/metro-config.
 * @type {import('expo/metro-config').MetroConfig}
 */
module.exports = getDefaultConfig(__dirname);
