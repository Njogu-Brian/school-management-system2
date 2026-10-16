import type { PageSection } from "@/types/website";

export type CmsGridItem = {
  title: string;
  description?: string;
  icon?: string;
};

export type CmsPhoto = {
  src: string;
  title: string;
  caption?: string;
  href?: string;
};

export function sectionSettings(section: PageSection): Record<string, unknown> {
  return section.settings ?? {};
}

const RETIRED_IMAGE_MARKERS = ["2025-admissions", "whatsapp-image-2024-10-28-at-18.13.54"];

export function isRetiredImage(src: string): boolean {
  const value = src.toLowerCase();
  return RETIRED_IMAGE_MARKERS.some((marker) => value.includes(marker));
}

export function sectionImage(section: PageSection): string | undefined {
  const s = sectionSettings(section);
  const url = s.image_url ?? s.imageUrl;
  if (typeof url !== "string" || url.length === 0 || isRetiredImage(url)) {
    return undefined;
  }
  return url;
}

export function sectionItems(section: PageSection): CmsGridItem[] {
  const s = sectionSettings(section);
  const raw = s.items ?? s.cards ?? s.pillars;
  if (Array.isArray(raw)) {
    return raw as CmsGridItem[];
  }
  if (typeof raw === "string") {
    try {
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  }
  return [];
}

export function sectionPhotos(section: PageSection): CmsPhoto[] {
  const s = sectionSettings(section);
  const raw = s.photos;
  if (Array.isArray(raw)) {
    return (raw as CmsPhoto[]).filter((photo) => photo?.src && !isRetiredImage(photo.src));
  }
  if (typeof raw === "string") {
    try {
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  }
  return [];
}

export function splitParagraphs(content?: string): string[] {
  if (!content?.trim()) {
    return [];
  }
  return content
    .split(/\n{2,}/)
    .map((p) => p.trim())
    .filter(Boolean);
}

export function sectionCta(section: PageSection): { href: string; label: string } {
  const s = sectionSettings(section);
  return {
    href: (s.href as string) || (s.link_url as string) || "/contact",
    label: (s.label as string) || (s.cta_label as string) || "Learn More",
  };
}

export function sectionVariant(section: PageSection): string | undefined {
  const s = sectionSettings(section);
  const v = s.variant ?? s.block;
  return typeof v === "string" ? v : undefined;
}

export function useGalleryCatalog(section: PageSection): boolean {
  return sectionSettings(section).use_gallery_catalog === true;
}
