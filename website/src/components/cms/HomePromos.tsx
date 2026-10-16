"use client";

import type { PageSection } from "@/types/website";
import { assetPath } from "@/lib/assetPath";
import { sectionPhotos } from "@/lib/cmsSections";

const FALLBACK_BANNER = {
  src: assetPath("/images/2027-admissions-open.jpg"),
  title: "2027 Admissions Now Open",
  href: "/admissions/apply",
};

export function HomePromos({ sections }: { sections: PageSection[] }) {
  const section = sections.find((item) => item.key === "home_promos");
  const photos = section ? sectionPhotos(section) : [];
  const banners = photos.length > 0 ? photos : [FALLBACK_BANNER];

  return (
    <section className="bg-white py-8">
      <div className="rk-container flex flex-col gap-6">
        {banners.map((photo) => (
          <a key={photo.src} href={photo.href || "/admissions/apply"} className="block overflow-hidden rounded-rk-xl shadow-rk-lg">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={photo.src} alt={photo.title || "2027 Admissions Now Open"} className="h-auto w-full" />
          </a>
        ))}
      </div>
    </section>
  );
}
