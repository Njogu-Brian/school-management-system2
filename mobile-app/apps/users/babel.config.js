/**
 * Babel config for the Users App.
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
          },
        },
      ],
      'react-native-worklets/plugin',
    ],
  };
};
