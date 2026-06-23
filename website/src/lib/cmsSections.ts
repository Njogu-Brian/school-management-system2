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
};

export function sectionSettings(section: PageSection): Record<string, unknown> {
  return section.settings ?? {};
}

export function sectionImage(section: PageSection): string | undefined {
  const s = sectionSettings(section);
  const url = s.image_url ?? s.imageUrl;
  return typeof url === "string" && url.length > 0 ? url : undefined;
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
    return raw as CmsPhoto[];
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
