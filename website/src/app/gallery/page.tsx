"use client";

import { SiteShell } from "@/components/layout/SiteShell";
import { useGallery } from "@/hooks/useWebsiteData";

export default function GalleryPage() {
  const { data, isLoading } = useGallery();

  return (
    <SiteShell>
      <section className="bg-[#2a1145] py-16 text-white"><div className="mx-auto max-w-6xl px-4 text-center"><h1 className="font-serif text-4xl font-bold">Gallery</h1></div></section>
      <section className="mx-auto max-w-6xl px-4 py-16 lg:px-8">
        {isLoading && <p>Loading gallery...</p>}
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {(data?.data || []).map((item) => (
            <figure key={item.id} className="overflow-hidden rounded-2xl shadow-lg">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={item.url} alt={item.alt_text || item.title} className="h-56 w-full object-cover" loading="lazy" />
              <figcaption className="bg-white p-3 text-sm text-[#5B2C8E]">{item.title}</figcaption>
            </figure>
          ))}
        </div>
      </section>
    </SiteShell>
  );
}
