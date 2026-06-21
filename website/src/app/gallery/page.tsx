"use client";

import { RichPage, PageHero, SectionBlock } from "@/components/layout/RichPage";
import { LEGACY_IMAGES } from "@/content/schoolContent";
import { useGallery } from "@/hooks/useWebsiteData";

const FALLBACK_GALLERY = [
  { id: 1, title: "Campus Life", url: LEGACY_IMAGES.campus, alt_text: "Royal Kings campus" },
  { id: 2, title: "2025 Admissions", url: LEGACY_IMAGES.admissions, alt_text: "Admissions open" },
  { id: 3, title: "Royal Kings School", url: LEGACY_IMAGES.logo, alt_text: "School logo" },
];

export default function GalleryPage() {
  const { data, isLoading } = useGallery();
  const items = (data?.data?.length ? data.data : FALLBACK_GALLERY) as typeof FALLBACK_GALLERY;

  return (
    <RichPage>
      <PageHero title="Gallery" subtitle="Moments from campus life, events, and celebrations at Royal Kings Wangige." image={LEGACY_IMAGES.campus} />
      <SectionBlock>
        {isLoading && <p className="text-center text-[var(--rk-purple)]">Loading gallery...</p>}
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
          {items.map((item) => (
            <figure key={item.id} className="group overflow-hidden rounded-2xl shadow-lg ring-1 ring-[var(--rk-border)]">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src={item.url}
                alt={item.alt_text || item.title}
                className="h-52 w-full object-cover transition duration-300 group-hover:scale-105 sm:h-56"
                loading="lazy"
              />
              <figcaption className="bg-white p-3 text-sm font-medium text-[var(--rk-purple)]">{item.title}</figcaption>
            </figure>
          ))}
        </div>
      </SectionBlock>
    </RichPage>
  );
}
