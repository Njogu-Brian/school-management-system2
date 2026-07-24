/**
 * Start the Admin Expo app with this package as Metro root (not the monorepo root).
 */
process.env.EXPO_NO_METRO_WORKSPACE_ROOT = '1';

const { spawn } = require('child_process');
const args = process.argv.slice(2);
const child = spawn('npx', ['expo', 'start', ...args], {
  stdio: 'inherit',
  shell: true,
  env: process.env,
});
child.on('exit', (code) => process.exit(code ?? 0));
