const { getDefaultConfig } = require('expo/metro-config');
const path = require('path');

/**
 * Monorepo-aware Metro for the Admin App.
 * Watch shared packages only; block the Users app to avoid haste-map collisions.
 */
const projectRoot = __dirname;
const workspaceRoot = path.resolve(projectRoot, '../..');

const config = getDefaultConfig(projectRoot);

config.watchFolders = [
  path.resolve(workspaceRoot, 'packages/core'),
  path.resolve(workspaceRoot, 'packages/ui'),
  path.resolve(workspaceRoot, 'node_modules'),
];

config.resolver.nodeModulesPaths = [
  path.resolve(projectRoot, 'node_modules'),
  path.resolve(workspaceRoot, 'node_modules'),
];

config.resolver.extraNodeModules = {
  semver: path.resolve(workspaceRoot, 'node_modules/semver'),
  'webidl-conversions': path.resolve(workspaceRoot, 'node_modules/webidl-conversions'),
};

const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
config.resolver.blockList = [
  new RegExp(`^${escapeRegExp(path.resolve(workspaceRoot, 'apps/users'))}[/\\\\].*`),
];

module.exports = config;
