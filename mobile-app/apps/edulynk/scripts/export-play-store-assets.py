"""Resize Edulynk graphics to Google Play Console exact sizes (no alpha)."""
from __future__ import annotations

from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
ASSETS = ROOT / "assets" / "play-store"
SOURCE = Path(
    r"C:\Users\brian\.cursor\projects\d-Projects-school-management-system2-school-management-system2\assets"
)
ICON_SRC = ROOT / "assets" / "icon.png"


def flatten(im: Image.Image, bg: tuple[int, int, int] = (255, 255, 255)) -> Image.Image:
    if im.mode in ("RGBA", "LA") or (im.mode == "P" and "transparency" in im.info):
        rgba = im.convert("RGBA")
        base = Image.new("RGB", rgba.size, bg)
        base.paste(rgba, mask=rgba.split()[-1])
        return base
    return im.convert("RGB")


def cover(im: Image.Image, size: tuple[int, int], bg: tuple[int, int, int]) -> Image.Image:
    im = flatten(im, bg)
    tw, th = size
    scale = max(tw / im.width, th / im.height)
    nw, nh = int(im.width * scale), int(im.height * scale)
    im = im.resize((nw, nh), Image.Resampling.LANCZOS)
    left = (nw - tw) // 2
    top = (nh - th) // 2
    return im.crop((left, top, left + tw, top + th))


def main() -> None:
    ASSETS.mkdir(parents=True, exist_ok=True)

    icon = flatten(Image.open(ICON_SRC), (23, 105, 255))
    icon = icon.resize((512, 512), Image.Resampling.LANCZOS)
    icon.save(ASSETS / "app-icon-512x512.png", "PNG", optimize=True)

    feature = cover(Image.open(SOURCE / "edulynk-feature-graphic.png"), (1024, 500), (7, 26, 61))
    feature.save(ASSETS / "feature-graphic-1024x500.png", "PNG", optimize=True)

    shot1 = cover(Image.open(SOURCE / "edulynk-screenshot-school-code.png"), (1080, 1920), (246, 249, 252))
    shot1.save(ASSETS / "screenshot-01-school-code.png", "PNG", optimize=True)

    shot2 = cover(Image.open(SOURCE / "edulynk-screenshot-login.png"), (1080, 1920), (7, 26, 61))
    shot2.save(ASSETS / "screenshot-02-sign-in.png", "PNG", optimize=True)

    for p in sorted(ASSETS.glob("*.png")):
        im = Image.open(p)
        print(f"{p.name}: {im.size[0]}x{im.size[1]} {im.mode} {p.stat().st_size // 1024}KB")


if __name__ == "__main__":
    main()
