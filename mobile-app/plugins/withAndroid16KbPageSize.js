const { withGradleProperties } = require('@expo/config-plugins');

/** NDK r28+ aligns 64-bit .so LOAD segments to 16 KB for Play Console. */
const NDK_VERSION = '28.2.13676358';

const PROPERTIES = {
  'expo.useLegacyPackaging': 'false',
  'android.enableMinifyInReleaseBuilds': 'true',
  'android.enableShrinkResourcesInReleaseBuilds': 'true',
  'android.ndkVersion': NDK_VERSION,
};

function upsertProperty(items, key, value) {
  const existing = items.find((item) => item.type === 'property' && item.key === key);
  if (existing) {
    existing.value = value;
    return;
  }
  items.push({ type: 'property', key, value });
}

/**
 * Play Console rejects AABs whose native libraries are not 16 KB page-size
 * compatible. Pin NDK r28, keep uncompressed JNI packaging, and turn on R8
 * so a mapping.txt is produced for the deobfuscation warning.
 */
function withAndroid16KbPageSize(config) {
  return withGradleProperties(config, (mod) => {
    for (const [key, value] of Object.entries(PROPERTIES)) {
      upsertProperty(mod.modResults, key, value);
    }
    return mod;
  });
}

module.exports = withAndroid16KbPageSize;
