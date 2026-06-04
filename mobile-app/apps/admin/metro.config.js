const { getDefaultConfig } = require('expo/metro-config');
const path = require('path');

/**
 * Monorepo-aware Metro for the Admin App.
 *
 * Watches the workspace root so shared packages (@erp/core, @erp/ui) hot-reload, and
 * resolves modules from both the app and the hoisted workspace node_modules.
 * @type {import('expo/metro-config').MetroConfig}
 */
const projectRoot = __dirname;
const workspaceRoot = path.resolve(projectRoot, '../..');

const config = getDefaultConfig(projectRoot);

config.watchFolders = [workspaceRoot];
config.resolver.nodeModulesPaths = [
  path.resolve(projectRoot, 'node_modules'),
  path.resolve(workspaceRoot, 'node_modules'),
];
config.resolver.disableHierarchicalLookup = true;

module.exports = config;
