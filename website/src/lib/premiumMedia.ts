import type { GalleryItem } from "@/types/website";
import type { BrandItem } from "@/types/brand";

/** Pick best URL for display size from optimized variants */
export function mediaUrl(item: GalleryItem | undefined, size: "sm" | "md" | "lg" | "xl" = "lg"): string | undefined {
  if (!item) return undefined;
  if (size === "sm" && item.url_sm) return item.url_sm;
  if (size === "md" && item.url_md) return item.url_md;
  if (size === "lg" && item.url_lg) return item.url_lg;
  return item.optimized_url || item.url;
}

/** Merge premium CMS media into brand items by index (homepage sections) */
export function enrichBrandItemsWithMedia(
  items: BrandItem[],
  media: GalleryItem[],
): BrandItem[] {
  if (!media.length) return items;

  return items.map((item, index) => {
    const pick = media[index % media.length];
    const url = mediaUrl(pick, "lg");
    if (!url) return item;

    return {
      ...item,
      image_url: url,
      settings: {
        ...item.settings,
        media_id: pick.id,
        srcset: pick.srcset,
        alt_text: pick.alt_text || item.title,
      },
    };
  });
}

export function galleryItemsFromResponse(payload: { data?: GalleryItem[] } | GalleryItem[]): GalleryItem[] {
  if (Array.isArray(payload)) return payload;
  return payload.data ?? [];
}
