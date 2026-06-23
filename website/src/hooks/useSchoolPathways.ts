"use client";

import { useMemo } from "react";
import { useHomepage } from "@/hooks/useWebsiteData";
import { usePremiumGallery } from "@/hooks/usePremiumMedia";
import {
  DEFAULT_SCHOOL_PATHWAYS,
  FIND_YOUR_PLACE_SUBTITLE,
  type SchoolPathway,
} from "@/content/schoolPathways";
import type { PageSection } from "@/types/website";
import { enrichBrandItemsWithMedia, mediaUrl } from "@/lib/premiumMedia";
import type { BrandItem } from "@/types/brand";

function sectionToPathway(section: PageSection): SchoolPathway {
  const settings = section.settings ?? {};

  return {
    id: section.key,
    title: section.title ?? "",
    subtitle: section.subtitle ?? "",
    body: section.content ?? "",
    ctaLabel: (settings.cta_label as string) || "Explore",
    linkUrl: (settings.link_url as string) || "/academics",
    imageUrl: (settings.image_url as string) || undefined,
    srcset: (settings.srcset as string) || undefined,
  };
}

function enrichPathwaysWithPremium(pathways: SchoolPathway[], premiumMedia: ReturnType<typeof usePremiumGallery>["data"]): SchoolPathway[] {
  if (!premiumMedia?.length) return pathways;

  const asBrandItems: BrandItem[] = pathways.map((p) => ({
    title: p.title,
    image_url: p.imageUrl,
    settings: { srcset: p.srcset },
  }));

  const enriched = enrichBrandItemsWithMedia(asBrandItems, premiumMedia);

  return pathways.map((p, i) => {
    const pick = premiumMedia[i % premiumMedia.length];
    const imageUrl = p.imageUrl || mediaUrl(pick, "lg") || pick?.url;
    const srcset = p.srcset || pick?.srcset;

    return {
      ...p,
      imageUrl,
      srcset: srcset || (enriched[i]?.settings?.srcset as string | undefined),
    };
  });
}

export function useSchoolPathways() {
  const { data: homepage, isLoading } = useHomepage();
  const { data: premiumMedia = [] } = usePremiumGallery({ per_page: 12 });

  const intro = useMemo(() => {
    const introSection = homepage?.page?.sections?.find(
      (s) => s.type === "school_pathways_intro" || s.key === "find_your_place",
    );
    return {
      subtitle: introSection?.subtitle || FIND_YOUR_PLACE_SUBTITLE,
    };
  }, [homepage]);

  const pathways = useMemo(() => {
    const cmsSections =
      homepage?.page?.sections
        ?.filter((s) => s.type === "school_pathway")
        .sort((a, b) => a.sort_order - b.sort_order) ?? [];

    const base =
      cmsSections.length > 0
        ? cmsSections.map(sectionToPathway).filter((p) => p.title)
        : DEFAULT_SCHOOL_PATHWAYS;

    return enrichPathwaysWithPremium(base, premiumMedia);
  }, [homepage, premiumMedia]);

  return { pathways, intro, isLoading };
}
