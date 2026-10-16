"""Cut school-bus photos out of their backgrounds and save transparent PNGs."""
from collections import deque
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "website" / "public" / "images"
ASSETS = Path(r"C:\Users\brian\.cursor\projects\d-Projects-school-management-system2-school-management-system2\assets")
ISUZU = next(ASSETS.glob("*NMR85*"))
COACH = next(ASSETS.glob("*images__4_*"))


def is_yellow(r, g, b):
    return r > 165 and g > 120 and b < 110 and (r + g) / 2 > b + 55


def is_dark(r, g, b):
    return r < 90 and g < 90 and b < 90


def cut_black_backdrop(src: Path, dest: Path):
    im = Image.open(src).convert("RGBA")
    w, h = im.size
    pix = im.load()
    seen = [[False] * w for _ in range(h)]
    q = deque()

    def push(x, y):
        if x < 0 or y < 0 or x >= w or y >= h or seen[y][x]:
            return
        r, g, b, a = pix[x, y]
        if r < 28 and g < 28 and b < 28:
            seen[y][x] = True
            q.append((x, y))

    for x in range(w):
        push(x, 0)
        push(x, h - 1)
    for y in range(h):
        push(0, y)
        push(w - 1, y)

    while q:
        x, y = q.popleft()
        r, g, b, _ = pix[x, y]
        pix[x, y] = (r, g, b, 0)
        push(x + 1, y)
        push(x - 1, y)
        push(x, y + 1)
        push(x, y - 1)

    crop_and_save(im, dest)


def filled_body_mask(im: Image.Image) -> Image.Image:
    w, h = im.size
    pix = im.load()
    mask = Image.new("L", (w, h), 0)
    mp = mask.load()
    for y in range(h):
        for x in range(w):
            r, g, b, _ = pix[x, y]
            if is_yellow(r, g, b):
                mp[x, y] = 255
    mask = mask.filter(ImageFilter.MaxFilter(9)).filter(ImageFilter.MinFilter(7))
    inv = Image.eval(mask, lambda v: 255 - v)
    ImageDraw.floodfill(inv, (0, 0), 128)
    return Image.eval(inv, lambda v: 0 if v == 128 else 255)


def cut_yellow_bus(src: Path, dest: Path):
    im = Image.open(src).convert("RGBA")
    w, h = im.size
    pix = im.load()
    core = filled_body_mask(im)
    halo = core.filter(ImageFilter.MaxFilter(15))
    core_px = core.load()
    halo_px = halo.load()
    for y in range(h):
        for x in range(w):
            r, g, b, _ = pix[x, y]
            if core_px[x, y] == 255:
                continue
            if halo_px[x, y] == 255 and (is_yellow(r, g, b) or is_dark(r, g, b)):
                continue
            pix[x, y] = (r, g, b, 0)
    crop_and_save(im, dest)


def fill_interior_holes(path: Path):
    im = Image.open(path).convert("RGBA")
    w, h = im.size
    pix = im.load()
    seen = [[False] * w for _ in range(h)]
    q = deque()

    def push(x, y):
        if x < 0 or y < 0 or x >= w or y >= h or seen[y][x]:
            return
        if pix[x, y][3] == 0:
            seen[y][x] = True
            q.append((x, y))

    for x in range(w):
        push(x, 0)
        push(x, h - 1)
    for y in range(h):
        push(0, y)
        push(w - 1, y)
    while q:
        x, y = q.popleft()
        push(x + 1, y)
        push(x - 1, y)
        push(x, y + 1)
        push(x, y - 1)

    glass = (32, 42, 52, 255)
    for y in range(h):
        for x in range(w):
            if pix[x, y][3] == 0 and not seen[y][x]:
                pix[x, y] = glass
    im.save(path)


def crop_and_save(im: Image.Image, dest: Path):
    alpha = im.getchannel("A")
    bbox = alpha.getbbox()
    if bbox:
        im = im.crop(bbox)
    dest.parent.mkdir(parents=True, exist_ok=True)
    im.save(dest, "PNG")
    print(dest.name, im.size)


if __name__ == "__main__":
    isuzu = OUT / "school-bus-isuzu.png"
    coach = OUT / "school-bus-coach.png"
    cut_black_backdrop(ISUZU, isuzu)
    fill_interior_holes(isuzu)
    cut_yellow_bus(COACH, coach)
    fill_interior_holes(coach)
