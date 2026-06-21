#!/usr/bin/env python3
from collections import Counter
from io import BytesIO
from urllib.request import urlopen

from PIL import Image

URLS = [
    "https://royalkingsschools.sc.ke/assets/images/royal-logo-small-192x192.png",
    "https://erp.royalkingsschools.sc.ke/images/logo.png",
]


def extract(url: str) -> None:
    data = urlopen(url, timeout=20).read()
    img = Image.open(BytesIO(data)).convert("RGBA")
    colors = []
    for r, g, b, a in img.getdata():
        if a < 128:
            continue
        lum = 0.299 * r + 0.587 * g + 0.114 * b
        if lum < 35 or lum > 180:
            continue
        if b > 70 and r > 45 and g < min(r, b) * 0.6:
            colors.append((r, g, b))
    print(url)
    for rgb, n in Counter(colors).most_common(6):
        print(f"  #{rgb[0]:02x}{rgb[1]:02x}{rgb[2]:02x} ({n})")


for u in URLS:
    try:
        extract(u)
    except Exception as e:
        print(u, e)
