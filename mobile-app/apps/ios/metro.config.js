const { getDefaultConfig } = require('expo/metro-config');
const path = require('path');

/**
 * Combined iOS Metro config.
 * Watches admin + users *source* only (not their native folders / entry files)
 * so haste-map collisions from the sibling Android apps are avoided.
 */
const projectRoot = __dirname;
const workspaceRoot = path.resolve(projectRoot, '../..');
const adminRoot = path.resolve(workspaceRoot, 'apps/admin');
const usersRoot = path.resolve(workspaceRoot, 'apps/users');

const config = getDefaultConfig(projectRoot);

config.watchFolders = [
  path.resolve(workspaceRoot, 'packages/core'),
  path.resolve(workspaceRoot, 'packages/ui'),
  path.resolve(adminRoot, 'src'),
  path.resolve(usersRoot, 'src'),
  path.resolve(workspaceRoot, 'node_modules'),
];

config.resolver.nodeModulesPaths = [
  path.resolve(projectRoot, 'node_modules'),
  path.resolve(workspaceRoot, 'node_modules'),
];

config.resolver.extraNodeModules = {
  semver: path.resolve(workspaceRoot, 'node_modules/semver'),
  'webidl-conversions': path.resolve(workspaceRoot, 'node_modules/webidl-conversions'),
  'expo-notifications': path.resolve(workspaceRoot, 'node_modules/expo-notifications'),
  'expo-device': path.resolve(workspaceRoot, 'node_modules/expo-device'),
  '@admin': path.resolve(adminRoot, 'src'),
  '@users': path.resolve(usersRoot, 'src'),
};

const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
config.resolver.blockList = [
  new RegExp(`^${escapeRegExp(path.join(adminRoot, 'android'))}[/\\\\].*`),
  new RegExp(`^${escapeRegExp(path.join(adminRoot, 'ios'))}[/\\\\].*`),
  new RegExp(`^${escapeRegExp(path.join(usersRoot, 'android'))}[/\\\\].*`),
  new RegExp(`^${escapeRegExp(path.join(usersRoot, 'ios'))}[/\\\\].*`),
  new RegExp(`^${escapeRegExp(path.join(adminRoot, 'index.js'))}$`),
  new RegExp(`^${escapeRegExp(path.join(adminRoot, 'App.tsx'))}$`),
  new RegExp(`^${escapeRegExp(path.join(usersRoot, 'index.js'))}$`),
  new RegExp(`^${escapeRegExp(path.join(usersRoot, 'App.tsx'))}$`),
];

module.exports = config;
