"use client";

import { SectionBlock, PhotoGrid } from "@/components/layout/RichPage";
import { useGallery } from "@/hooks/useWebsiteData";
import { mediaUrl } from "@/lib/premiumMedia";

export function CatalogPhotoGrid({
  title,
  subtitle,
  sectionKey,
}: {
  title?: string;
  subtitle?: string;
  sectionKey?: string;
}) {
  const { data, isLoading, isError } = useGallery();

  const photos = (data ?? [])
    .map((item) => {
      const src = mediaUrl(item, "md") || item.url;
      if (!src) return null;
      return {
        src,
        title: item.title || item.alt_text || "Gallery",
        caption: item.alt_text || undefined,
      };
    })
    .filter((p): p is { src: string; title: string; caption?: string } => !!p);

  if (isLoading) {
    return (
      <SectionBlock title={title} intro={subtitle} id={sectionKey}>
        <div className="py-16 text-center text-[var(--rk-muted)]">Loading gallery…</div>
      </SectionBlock>
    );
  }

  if (isError || photos.length === 0) {
    return (
      <SectionBlock title={title} intro={subtitle} id={sectionKey}>
        <div className="rounded-2xl border border-dashed border-[var(--rk-border)] bg-white px-6 py-16 text-center">
          <p className="font-serif text-lg text-[var(--rk-text)]">No gallery photos yet</p>
          <p className="mt-2 text-sm text-[var(--rk-muted)]">
            Upload and approve images in Website CMS → Media Library. Settings login backgrounds are separate.
          </p>
        </div>
      </SectionBlock>
    );
  }

  return (
    <SectionBlock title={title} intro={subtitle} id={sectionKey}>
      <PhotoGrid photos={photos} />
    </SectionBlock>
  );
}
