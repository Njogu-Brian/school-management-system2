/**
 * Babel config for the combined iOS app.
 * Aliases both admin and users source trees so every existing screen is reused.
 */
module.exports = function (api) {
  api.cache(true);
  return {
    presets: ['babel-preset-expo'],
    plugins: [
      [
        'module-resolver',
        {
          root: ['./src'],
          extensions: ['.ios.js', '.android.js', '.js', '.ts', '.tsx', '.json'],
          alias: {
            '@erp/core': '../../packages/core/src',
            '@erp/ui': '../../packages/ui/src',
            '@admin': '../admin/src',
            '@users': '../users/src',
          },
        },
      ],
      'react-native-worklets/plugin',
    ],
  };
};
