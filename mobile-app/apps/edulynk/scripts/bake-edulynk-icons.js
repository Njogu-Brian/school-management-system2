/**
 * Bake Edulynk mark into Expo + Play Store icon slots.
 * Source: assets/edulynk-mark-source.png (official mark on black).
 */
const path = require('path');
const sharp = require('sharp');

const ASSETS = path.join(__dirname, '..', 'assets');
const PLAY = path.join(ASSETS, 'play-store');
const SOURCE = path.join(ASSETS, 'edulynk-mark-source.png');
const NAVY = { r: 7, g: 26, b: 61, alpha: 1 };

async function bakeSquare(size, outPath, { padRatio = 0.12 } = {}) {
  const pad = Math.round(size * padRatio);
  const inner = size - pad * 2;
  const mark = await sharp(SOURCE)
    .resize(inner, inner, { fit: 'contain', background: NAVY })
    .png()
    .toBuffer();

  await sharp({
    create: {
      width: size,
      height: size,
      channels: 4,
      background: NAVY,
    },
  })
    .composite([{ input: mark, left: pad, top: pad }])
    .png()
    .toFile(outPath);

  console.log('wrote', path.basename(outPath), `${size}x${size}`);
}

async function bakeSplash() {
  const size = 1024;
  const markSize = 720;
  const mark = await sharp(SOURCE)
    .resize(markSize, markSize, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } })
    .png()
    .toBuffer();
  const left = Math.round((size - markSize) / 2);
  await sharp({
    create: {
      width: size,
      height: size,
      channels: 4,
      background: { r: 0, g: 0, b: 0, alpha: 0 },
    },
  })
    .composite([{ input: mark, left, top: left }])
    .png()
    .toFile(path.join(ASSETS, 'splash-icon.png'));
  console.log('wrote splash-icon.png');
}

async function main() {
  await bakeSquare(1024, path.join(ASSETS, 'icon.png'), { padRatio: 0.08 });
  await bakeSquare(1024, path.join(ASSETS, 'adaptive-icon.png'), { padRatio: 0.18 });
  await bakeSplash();
  await bakeSquare(512, path.join(PLAY, 'app-icon-512x512.png'), { padRatio: 0.08 });
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
