/**
 * Regenerate app icons with ~20% safe padding so the logo is not cropped in the launcher.
 *
 * Usage (from mobile-app/apps/admin):
 *   node scripts/pad-app-icons.mjs
 *
 * Requires: npm install sharp --save-dev (in apps/admin or workspace root)
 */
import { existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const assetsDir = join(__dirname, '..', 'assets');
const source = join(assetsDir, 'splash-icon.png');
const targets = ['icon.png', 'adaptive-icon.png'];

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
  const inner = Math.round(size * 0.6); // ~20% margin each side
  const offset = Math.round((size - inner) / 2);

  const logo = await sharp(source).resize(inner, inner, { fit: 'contain' }).png().toBuffer();
  const canvas = sharp({
    create: {
      width: size,
      height: size,
      channels: 4,
      background: { r: 57, g: 7, b: 84, alpha: 1 },
    },
  })
    .composite([{ input: logo, left: offset, top: offset }])
    .png();

  for (const name of targets) {
    const out = join(assetsDir, name);
    await canvas.clone().toFile(out);
    console.log(`Wrote ${out}`);
  }
}

void main();
