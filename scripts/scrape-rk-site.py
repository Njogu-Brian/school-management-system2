#!/usr/bin/env python3
import re
import urllib.request

BASE = "https://royalkingsschools.sc.ke"
paths = ["/", "/index.html", "/page1.html", "/page2.html", "/page3.html", "/page4.html", "/page5.html"]

for path in paths:
    try:
        url = BASE + path
        html = urllib.request.urlopen(url, timeout=20).read().decode("utf-8", errors="ignore")
        print("===", url, "===")
        for m in re.findall(r"<h[1-4][^>]*>(.*?)</h[1-4]>", html, re.I | re.S):
            t = re.sub(r"<[^>]+>", "", m).strip()
            if t:
                print("H:", t[:120])
        for m in re.findall(r"<p[^>]*>(.*?)</p>", html, re.I | re.S)[:8]:
            t = re.sub(r"<[^>]+>", "", m).strip()
            if len(t) > 30:
                print("P:", t[:200])
        imgs = re.findall(r'src="([^"]+)"', html)
        for img in imgs:
            if "logo" in img.lower() or img.endswith((".png", ".jpg", ".webp")):
                print("IMG:", img)
    except Exception as e:
        print(path, e)
