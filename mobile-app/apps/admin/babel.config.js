/**
 * Babel config for the Admin App.
 *
 * - `babel-preset-expo` auto-includes the worklets/reanimated transform on SDK 54,
 *   so no separate reanimated plugin is needed (matches the Staff App).
 * - `module-resolver` maps the shared workspace packages to their source so the apps
 *   consume @erp/* without a build step (build plan §2).
 */
module.exports = function (api) {
  api.cache(true);
  return {
    presets: ['babel-preset-expo'],
    plugins: [
      'react-native-worklets/plugin',
      [
        'module-resolver',
        {
          root: ['./src'],
          extensions: ['.ios.js', '.android.js', '.js', '.ts', '.tsx', '.json'],
          alias: {
            '@erp/core': '../../packages/core/src',
            '@erp/ui': '../../packages/ui/src',
          },
        },
      ],
    ],
  };
};
