const { getDefaultConfig } = require('expo/metro-config');
const path = require('path');

/**
 * Monorepo-aware Metro for the Users App.
 */
const projectRoot = __dirname;
const workspaceRoot = path.resolve(projectRoot, '../..');

const config = getDefaultConfig(projectRoot);

config.watchFolders = [workspaceRoot];
config.resolver.nodeModulesPaths = [
  path.resolve(projectRoot, 'node_modules'),
  path.resolve(workspaceRoot, 'node_modules'),
];

config.resolver.extraNodeModules = {
  semver: path.resolve(workspaceRoot, 'node_modules/semver'),
  'webidl-conversions': path.resolve(workspaceRoot, 'node_modules/webidl-conversions'),
};

module.exports = config;
