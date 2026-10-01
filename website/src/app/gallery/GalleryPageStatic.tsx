"use client";

import { RichPage, PageHero, SectionBlock, CtaBanner } from "@/components/layout/RichPage";
import { CatalogPhotoGrid } from "@/components/cms/CatalogPhotoGrid";
import { useHeroMedia } from "@/hooks/usePremiumMedia";
import { mediaUrl } from "@/lib/premiumMedia";

export function GalleryPageStatic() {
  const { data: hero } = useHeroMedia();
  const heroSrc = hero ? mediaUrl(hero, "lg") || hero.url : undefined;

  return (
    <RichPage>
      <PageHero
        title="Gallery"
        subtitle="A sneak peek of how Learning is Fun! — real photos from classrooms, sports, arts, devotions, and school events at Royal Kings Wangige."
        image={heroSrc}
      />
      <CatalogPhotoGrid
        title="Campus Life in Pictures"
        subtitle="Managed from Website CMS → Media Library."
        sectionKey="gallery-static"
      />
      <SectionBlock alt>
        <CtaBanner title="See It In Person" body="Book a campus tour and experience Royal Kings for yourself." href="/admissions" label="Book a Tour" />
      </SectionBlock>
    </RichPage>
  );
}
