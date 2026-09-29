/**
 * Load KEY=VALUE pairs from a .env file into process.env (does not override existing).
 * Plain JS so Expo app.config can require() it without TS resolution.
 */
function loadEnvFile(filePath) {
  try {
    const fs = require('fs');
    if (!fs.existsSync(filePath)) return;
    const text = fs.readFileSync(filePath, 'utf8');
    for (const raw of text.split(/\r?\n/)) {
      const line = raw.trim();
      if (!line || line.startsWith('#')) continue;
      const eq = line.indexOf('=');
      if (eq <= 0) continue;
      const key = line.slice(0, eq).trim();
      let value = line.slice(eq + 1).trim();
      if (
        (value.startsWith('"') && value.endsWith('"')) ||
        (value.startsWith("'") && value.endsWith("'"))
      ) {
        value = value.slice(1, -1);
      }
      if (process.env[key] === undefined || process.env[key] === '') {
        process.env[key] = value;
      }
    }
  } catch {
    // ignore missing / unreadable env during config eval
  }
}

module.exports = { loadEnvFile };
