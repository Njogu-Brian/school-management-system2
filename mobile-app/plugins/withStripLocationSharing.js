const { withAndroidManifest } = require('@expo/config-plugins');

const LOCATION_PERMISSIONS = new Set([
  'android.permission.ACCESS_COARSE_LOCATION',
  'android.permission.ACCESS_FINE_LOCATION',
  'android.permission.ACCESS_BACKGROUND_LOCATION',
  'android.permission.FOREGROUND_SERVICE_LOCATION',
]);

/** Library service that makes Play Console require a location foreground-service declaration. */
const LOCATION_SERVICE = 'expo.modules.location.services.LocationTaskService';

function ensureArray(parent, key) {
  if (!parent[key]) parent[key] = [];
  if (!Array.isArray(parent[key])) parent[key] = [parent[key]];
  return parent[key];
}

/**
 * Users and Edulynk must ship without location so Play does not ask for a
 * background-location video or a location foreground-service declaration.
 * tools:node="remove" survives the Gradle manifest merge if a library adds them.
 */
function withStripLocationSharing(config) {
  return withAndroidManifest(config, (mod) => {
    const manifest = mod.modResults.manifest;
    manifest.$ = manifest.$ ?? {};
    if (!manifest.$['xmlns:tools']) {
      manifest.$['xmlns:tools'] = 'http://schemas.android.com/tools';
    }

    const permissions = ensureArray(manifest, 'uses-permission');
    for (const name of LOCATION_PERMISSIONS) {
      const existing = permissions.find((item) => item?.$?.['android:name'] === name);
      if (existing) {
        existing.$['tools:node'] = 'remove';
      } else {
        permissions.push({
          $: {
            'android:name': name,
            'tools:node': 'remove',
          },
        });
      }
    }

    const app = manifest.application?.[0];
    if (app) {
      const services = ensureArray(app, 'service');
      const existing = services.find((item) => item?.$?.['android:name'] === LOCATION_SERVICE);
      if (existing) {
        existing.$['tools:node'] = 'remove';
      } else {
        services.push({
          $: {
            'android:name': LOCATION_SERVICE,
            'tools:node': 'remove',
          },
        });
      }
    }

    return mod;
  });
}

module.exports = withStripLocationSharing;
