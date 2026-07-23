/**
 * Regenerate launcher icons from the circular school logo with Android safe-zone padding.
 * Strips the black square behind splash-icon.png so adaptive icons mask cleanly.
 *
 * Usage (from mobile-app/apps/admin):
 *   node scripts/pad-app-icons.mjs
 */
import { existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const assetsDir = join(__dirname, '..', 'assets');
const source = join(assetsDir, 'splash-icon.png');

/** Logo purple — matches adaptiveIcon / splash backgroundColor in app.config.ts */
const BG = { r: 57, g: 7, b: 84, alpha: 1 };

async function logoWithoutBlackBg(sharp, inputPath, size) {
  const { data, info } = await sharp(inputPath)
    .resize(size, size, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } })
    .ensureAlpha()
    .raw()
    .toBuffer({ resolveWithObject: true });

  for (let i = 0; i < data.length; i += 4) {
    // Treat near-black pixels as transparent (splash asset has a black square behind the circle).
    if (data[i] < 24 && data[i + 1] < 24 && data[i + 2] < 24) {
      data[i + 3] = 0;
    }
  }

  return sharp(data, { raw: { width: info.width, height: info.height, channels: 4 } }).png().toBuffer();
}

async function main() {
  let sharp;
  try {
    sharp = (await import('sharp')).default;
  } catch {
    console.error('Install sharp first: npm install sharp --save-dev');
    process.exit(1);
  }

  if (!existsSync(source)) {
    console.error(`Source not found: ${source}`);
    process.exit(1);
  }

  const size = 1024;
  const inner = Math.round(size * 0.72);
  const offset = Math.round((size - inner) / 2);
  const logo = await logoWithoutBlackBg(sharp, source, inner);

  await sharp({
    create: { width: size, height: size, channels: 4, background: BG },
  })
    .composite([{ input: logo, left: offset, top: offset }])
    .png()
    .toFile(join(assetsDir, 'icon.png'));

  await sharp({
    create: {
      width: size,
      height: size,
      channels: 4,
      background: { r: 0, g: 0, b: 0, alpha: 0 },
    },
  })
    .composite([{ input: logo, left: offset, top: offset }])
    .png()
    .toFile(join(assetsDir, 'adaptive-icon.png'));

  console.log('Wrote icon.png and adaptive-icon.png (black bg removed)');
}

void main();
