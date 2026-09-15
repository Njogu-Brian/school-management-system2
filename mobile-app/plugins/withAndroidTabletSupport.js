const { withAndroidManifest } = require('@expo/config-plugins');

const OPTIONAL_FEATURES = [
  'android.hardware.telephony',
  'android.hardware.touchscreen',
  'android.hardware.wifi',
  'android.hardware.camera',
  'android.hardware.camera.autofocus',
  'android.hardware.location',
  'android.hardware.location.gps',
];

const CONFIG_CHANGES = [
  'keyboard',
  'keyboardHidden',
  'orientation',
  'screenSize',
  'smallestScreenSize',
  'screenLayout',
  'uiMode',
  'density',
];

function ensureArray(parent, key) {
  if (!parent[key]) parent[key] = [];
  if (!Array.isArray(parent[key])) parent[key] = [parent[key]];
  return parent[key];
}

function upsertUsesFeature(manifest, name) {
  const features = ensureArray(manifest, 'uses-feature');
  const existing = features.find((item) => item?.$?.['android:name'] === name);
  if (existing) {
    existing.$['android:required'] = 'false';
    return;
  }
  features.push({
    $: {
      'android:name': name,
      'android:required': 'false',
    },
  });
}

/**
 * Single Play Store APK: declare large/xlarge screens, do not require phone
 * hardware, and keep the activity alive across tablet rotation / split-screen.
 */
function withAndroidTabletSupport(config) {
  return withAndroidManifest(config, (mod) => {
    const manifest = mod.modResults.manifest;
    manifest['supports-screens'] = [
      {
        $: {
          'android:smallScreens': 'true',
          'android:normalScreens': 'true',
          'android:largeScreens': 'true',
          'android:xlargeScreens': 'true',
          'android:anyDensity': 'true',
          'android:resizeable': 'true',
        },
      },
    ];

    for (const name of OPTIONAL_FEATURES) {
      upsertUsesFeature(manifest, name);
    }

    const app = manifest.application?.[0];
    const activities = app?.activity ?? [];
    const activity =
      activities.find((item) => item?.$?.['android:name'] === '.MainActivity') ?? activities[0];
    if (activity?.$) {
      const current = String(activity.$['android:configChanges'] ?? '')
        .split('|')
        .map((part) => part.trim())
        .filter(Boolean);
      const merged = new Set([...current, ...CONFIG_CHANGES]);
      activity.$['android:configChanges'] = [...merged].join('|');
      activity.$['android:resizeableActivity'] = 'true';
    }

    return mod;
  });
}

module.exports = withAndroidTabletSupport;
