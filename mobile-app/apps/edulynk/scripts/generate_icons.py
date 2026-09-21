"""Generate Edulynk launcher PNGs from the website mark (navy/blue/cyan)."""
from __future__ import annotations

import math
from pathlib import Path

try:
    from PIL import Image, ImageDraw
except ImportError as exc:
    raise SystemExit("Pillow is required: pip install pillow") from exc

ROOT = Path(__file__).resolve().parents[1] / "assets"
BRAND = (23, 105, 255, 255)  # #1769FF
NAVY = (7, 26, 61, 255)  # #071A3D
WHITE = (255, 255, 255, 255)
CYAN = (22, 199, 194, 255)


def rounded_rect(draw: ImageDraw.ImageDraw, box, radius: int, fill) -> None:
    draw.rounded_rectangle(box, radius=radius, fill=fill)


def draw_mark(size: int, *, fill=BRAND, stroke=WHITE, pad_ratio: float = 0.0) -> Image.Image:
    img = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    inset = int(size * pad_ratio)
    inner = size - inset * 2
    if inner <= 0:
        inner = size
        inset = 0
    radius = int(inner * (10 / 36))
    box = (inset, inset, inset + inner, inset + inner)
    rounded_rect(draw, box, radius, fill)

    # Scale website SVG paths (viewBox 36) into the inner square.
    def sx(x: float) -> float:
        return inset + inner * (x / 36)

    def sy(y: float) -> float:
        return inset + inner * (y / 36)

    stroke_w = max(2, int(inner * (2.3 / 36)))
    lines = [
        ((10, 13.5), (26, 13.5)),
        ((10, 18), (22.5, 18)),
        ((10, 22.5), (26, 22.5)),
    ]
    for (x1, y1), (x2, y2) in lines:
        draw.line([(sx(x1), sy(y1)), (sx(x2), sy(y2))], fill=stroke, width=stroke_w)
    r = inner * (2.2 / 36)
    cx, cy = sx(25.2), sy(18)
    draw.ellipse((cx - r, cy - r, cx + r, cy + r), fill=CYAN)
    return img


def main() -> None:
    ROOT.mkdir(parents=True, exist_ok=True)
    icon = draw_mark(1024)
    icon.save(ROOT / "icon.png")

    adaptive = draw_mark(1024, pad_ratio=0.18)
    adaptive.save(ROOT / "adaptive-icon.png")

    splash = Image.new("RGBA", (1284, 2778), NAVY)
    mark = draw_mark(640)
    x = (splash.width - mark.width) // 2
    y = (splash.height - mark.height) // 2
    splash.paste(mark, (x, y), mark)
    splash.save(ROOT / "splash-icon.png")

    # Expo also uses a square splash asset in some pipelines.
    square = Image.new("RGBA", (1024, 1024), NAVY)
    mark_sq = draw_mark(560)
    sx = (1024 - mark_sq.width) // 2
    sy = (1024 - mark_sq.height) // 2
    square.paste(mark_sq, (sx, sy), mark_sq)
    square.save(ROOT / "splash-icon.png")

    print(f"Wrote icons in {ROOT}")


if __name__ == "__main__":
    main()
