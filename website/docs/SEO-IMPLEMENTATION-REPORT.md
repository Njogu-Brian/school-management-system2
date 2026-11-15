# Royal Kings Premier School — Technical SEO Implementation Report

**Date:** 2026-11-05  
**Official domain:** https://royalkingsschools.sc.ke/  
**Public frontend:** `website/` (Next.js 16 App Router)  
**Backend:** Laravel API (`/api/website/*`) + ERP

---

## 1. Architecture / rendering audit

| Question | Finding |
|---|---|
| Blade + React hydrate? | No for the public marketing site |
| Inertia React? | No |
| Laravel API + standalone frontend? | **Yes** — Next.js App Router in `website/` |
| Production homepage | Already Next.js SSR/RSC HTML with `<title>`, meta description, and visible content (verified via `curl` against production) |

**Verdict:** Do **not** introduce another framework. All SEO work belongs in `website/`. Laravel remains CMS/API/ERP. Keep indexed URLs; add 301s for marketing aliases.

---

## 2. What was implemented

### SSR / crawlable landing pages
- `/preschool-wangige`
- `/creche-early-years`
- `/primary-school-wangige`
- `/junior-school-wangige`
- `/parent-info` (AI / parent FAQ hub)
- `/co-curricular` (replaces thin redirect with real content)

Each page is a server-rendered route with unique metadata, H1, substantial copy, internal links, FAQ blocks, and JSON-LD (BreadcrumbList + FAQPage where applicable).

### Metadata system
- Expanded `website/src/lib/seo.ts` (`PUBLIC_PAGES`, `pageMetadata`, School / WebSite / Breadcrumb / FAQ helpers)
- Homepage split: `page.tsx` (server + metadata) + `HomePageClient.tsx`
- Homepage H1 aligned to local SEO wording

### Technical SEO
- `app/sitemap.ts` — driven by `PUBLIC_PAGES` (includes new landings)
- `app/robots.ts` — allows AI search crawlers; disallows parent portal / track / api
- `public/llms.txt` — machine-readable site guide (supplement only)
- `next.config.ts` permanent redirects:
  - `/about-us` → `/about`
  - `/school-fees` → `/fees`
  - `/co-curricular-activities` → `/co-curricular`
  - `/school-transport` → `/transport`
  - `/news` → `/blog`
  - `/contact-us` → `/contact`
  - kindergarten/nursery aliases → `/preschool-wangige`

### Structured data
- Root layout: School + WebSite JSON-LD (`@id` stable)
- Programme / parent-info pages: BreadcrumbList + FAQPage

---

## 3. URL map (canonical vs aliases)

| Desired marketing URL | Behaviour |
|---|---|
| `/` | Canonical homepage |
| `/about-us` | 301 → `/about` |
| `/preschool-wangige` | New indexable page |
| `/creche-early-years` | New indexable page |
| `/primary-school-wangige` | New indexable page |
| `/junior-school-wangige` | New indexable page |
| `/admissions` | Existing |
| `/school-fees` | 301 → `/fees` |
| `/co-curricular-activities` | 301 → `/co-curricular` |
| `/school-transport` | 301 → `/transport` |
| `/gallery` | Existing |
| `/news` | 301 → `/blog` |
| `/contact-us` | 301 → `/contact` |

---

## 4. Still owned by school / ops (not code-only)

1. **Google Search Console** — verify property, submit `https://royalkingsschools.sc.ke/sitemap.xml`, inspect new URLs.
2. **GA4 conversion events** — wire `phone_click`, `whatsapp_click`, `admission_*`, etc. (site already has internal analytics posts; map to GA4 tags when measurement ID is available).
3. **Google Business Profile** — name/address/phone/website consistency; avoid duplicate department listings; honest review process.
4. **Core Web Vitals** — re-test with PageSpeed after deploy (images/fonts already partially optimised).
5. **Nginx** — ensure public `/sitemap.xml` and `/robots.txt` hit the Next.js site, not Laravel’s `/api/website/sitemap.xml`.
6. **Content calendar** — publish ≥2 quality articles/month under `/blog`.

---

## 5. Acceptance checks (local / post-deploy)

```bash
curl -sL http://localhost:3000/preschool-wangige | findstr /I "Preschool H1 Nurturing"
curl -sL http://localhost:3000/primary-school-wangige | findstr /I "Grades 1"
curl -sL http://localhost:3000/junior-school-wangige | findstr /I "Grades 7"
curl -sL http://localhost:3000/sitemap.xml
curl -sL http://localhost:3000/robots.txt
curl -sI http://localhost:3000/about-us
```

### Sprint 2 verification (2026-11-05)

| Check | Result |
|---|---|
| `npm run typecheck` | Pass |
| `npm run lint` | Pass (after blog + hooks lint fixes) |
| `npm run build` | Pass — all new routes prerendered (`preschool-wangige`, `primary-school-wangige`, `junior-school-wangige`, `parent-info`, `creche-early-years`, `co-curricular`) |
| Local preschool HTML | Title, description, canonical, H1, JSON-LD, programme copy present |
| Production `/preschool-wangige` etc. | **404 — not deployed yet** |
| Production `/about-us` | **404 — redirects not live yet** |
| Production `/sitemap.xml` `/robots.txt` | 200 (current production build; new URLs absent until deploy) |

**Implemented ≠ indexed.** Deploy Next.js `website/` to production, then re-run production curls and Search Console submission.

---

## 6. Files touched (primary)

- `website/src/lib/seo.ts`
- `website/src/content/seoLandingContent.ts`
- `website/src/components/seo/*`
- `website/src/app/page.tsx`, `HomePageClient.tsx`
- `website/src/app/{preschool-wangige,creche-early-years,primary-school-wangige,junior-school-wangige,parent-info,co-curricular}/page.tsx`
- `website/src/app/robots.ts`, `layout.tsx`, `sections/HeroSection.tsx`
- `website/next.config.ts`
- `website/public/llms.txt`
