"""Bake the official Edulynk mark into Expo + Play Store icon slots.

Prefer `bake-edulynk-icons.js` (Node + sharp). This script is a Pillow fallback.
"""
from __future__ import annotations

from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
ASSETS = ROOT / "assets"
PLAY = ASSETS / "play-store"
SOURCE = ASSETS / "edulynk-mark-source.png"
NAVY = (7, 26, 61, 255)


def bake_square(size: int, out: Path, pad_ratio: float = 0.12) -> None:
    pad = round(size * pad_ratio)
    inner = size - pad * 2
    src = Image.open(SOURCE).convert("RGBA")
    mark = src.resize((inner, inner), Image.Resampling.LANCZOS)
    canvas = Image.new("RGBA", (size, size), NAVY)
    canvas.paste(mark, (pad, pad), mark)
    canvas.convert("RGB").save(out, "PNG", optimize=True)
    print(f"wrote {out.name} {size}x{size}")


def bake_splash() -> None:
    size = 1024
    mark_size = 720
    src = Image.open(SOURCE).convert("RGBA")
    mark = src.resize((mark_size, mark_size), Image.Resampling.LANCZOS)
    canvas = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    offset = (size - mark_size) // 2
    canvas.paste(mark, (offset, offset), mark)
    canvas.save(ASSETS / "splash-icon.png", "PNG", optimize=True)
    print("wrote splash-icon.png")


def main() -> None:
    PLAY.mkdir(parents=True, exist_ok=True)
    bake_square(1024, ASSETS / "icon.png", 0.08)
    bake_square(1024, ASSETS / "adaptive-icon.png", 0.18)
    bake_splash()
    bake_square(512, PLAY / "app-icon-512x512.png", 0.08)


if __name__ == "__main__":
    main()
