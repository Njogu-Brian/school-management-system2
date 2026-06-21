#!/usr/bin/env python3
import re
import urllib.request

html = urllib.request.urlopen("https://royalkingsschools.sc.ke/", timeout=25).read().decode("utf-8", errors="ignore")
print("=== SOCIAL ===")
for m in re.findall(r'href="(https?://[^"]+)"', html):
    low = m.lower()
    if any(s in low for s in ["facebook", "instagram", "youtube", "tiktok", "twitter", "linkedin"]):
        print(m)
print("=== IMAGES ===")
for m in re.findall(r'src="(assets/images/[^"]+)"', html):
    print(m)
print("=== TEXT blocks ===")
for m in re.findall(r"<h[1-4][^>]*>([^<]{5,})", html):
    t = re.sub(r"\s+", " ", m).strip()
    if t and "Meet" not in t:
        print("H:", t[:100])
