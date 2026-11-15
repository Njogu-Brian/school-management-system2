import type { BrandContent, BrandItem } from "@/types/brand";
import type { CampusPhoto, CampusReel } from "@/content/campusMedia";
import type { SchoolPathway } from "@/content/schoolPathways";
import type { LiveOverrides } from "./types";

function t(overrides: LiveOverrides, key: string, fallback: string | undefined | null) {
  const base = fallback ?? "";
  return overrides.texts[key] ?? base;
}

function img(overrides: LiveOverrides, key: string, fallback: string | undefined | null) {
  const base = fallback ?? "";
  return overrides.images[key] ?? base;
}

function applyBrandItem(item: BrandItem, type: string, index: number, overrides: LiveOverrides): BrandItem {
  const base = `brand.${type}.${index}`;
  return {
    ...item,
    title: t(overrides, `${base}.title`, item.title),
    subtitle: t(overrides, `${base}.subtitle`, item.subtitle),
    body: t(overrides, `${base}.body`, item.body),
    image_url: img(overrides, `${base}.image`, item.image_url) || item.image_url,
  };
}

export function applyBrandOverrides(data: BrandContent, overrides: LiveOverrides): BrandContent {
  const out: BrandContent = { ...data };
  for (const [type, items] of Object.entries(data)) {
    if (!items?.length) continue;
    out[type] = items.map((item, index) => applyBrandItem(item, type, index, overrides));
  }
  return out;
}

export function applyPathwayOverrides(
  pathways: SchoolPathway[],
  overrides: LiveOverrides,
): SchoolPathway[] {
  return pathways.map((p, index) => {
    const id = String(p.id ?? index);
    const base = `pathway.${id}`;
    return {
      ...p,
      title: t(overrides, `${base}.title`, p.title),
      subtitle: t(overrides, `${base}.subtitle`, p.subtitle),
      body: t(overrides, `${base}.body`, p.body),
      ctaLabel: t(overrides, `${base}.cta`, p.ctaLabel),
      imageUrl: img(overrides, `${base}.image`, p.imageUrl) || p.imageUrl,
    };
  });
}

export function applyCampusPhotoOverrides(
  photos: CampusPhoto[],
  overrides: LiveOverrides,
): CampusPhoto[] {
  return photos.map((p) => {
    const base = `campus.photo.${p.id}`;
    return {
      ...p,
      src: img(overrides, `${base}.src`, p.src),
      title: t(overrides, `${base}.title`, p.title),
      caption: t(overrides, `${base}.caption`, p.caption),
      alt: t(overrides, `${base}.alt`, p.alt),
    };
  });
}

export function applyCampusReelOverrides(
  reels: CampusReel[],
  overrides: LiveOverrides,
): CampusReel[] {
  return reels.map((r) => {
    const base = `campus.reel.${r.id}`;
    return {
      ...r,
      title: t(overrides, `${base}.title`, r.title),
      caption: t(overrides, `${base}.caption`, r.caption),
      poster: img(overrides, `${base}.poster`, r.poster),
    };
  });
}
