"use client";

import { SiteShell } from "@/components/layout/SiteShell";
import { useEvents, useTestimonials, useWebsiteSettings, useHomepage } from "@/hooks/useWebsiteData";
import { useHeroMedia, usePremiumGallery } from "@/hooks/usePremiumMedia";
import { useBrandContent } from "@/hooks/useBrandContent";
import { useSchoolPathways } from "@/hooks/useSchoolPathways";
import { enrichBrandItemsWithMedia } from "@/lib/premiumMedia";
import { HeroSection } from "@/sections/HeroSection";
import { HomePromos } from "@/components/cms/HomePromos";
import { SchoolStoryFromCms } from "@/components/cms/SchoolStoryFromCms";
import { FindYourPlaceSection } from "@/sections/brand/FindYourPlaceSection";
import { OneJourney } from "@/sections/brand/OneJourney";
import { BeyondClassroom } from "@/sections/brand/BeyondClassroom";
import { VideoMoments } from "@/sections/brand/VideoMoments";
import { TestimonialsCarousel, LatestEvents } from "@/sections/HomeSections";
import Link from "next/link";
import { useMemo } from "react";
import { useLiveEditorOptional } from "@/components/live-editor/LiveEditorProvider";
import { applyBrandOverrides } from "@/lib/liveEditor/applyOverrides";
import { EMPTY_OVERRIDES } from "@/lib/liveEditor/types";
import { EditableText } from "@/components/live-editor/EditableText";

export function HomePageClient() {
  const { data: settings } = useWebsiteSettings();
  const brand = useBrandContent();
  const events = useEvents();
  const testimonials = useTestimonials(true);
  const { data: heroMedia } = useHeroMedia();
  const { data: premiumMedia = [] } = usePremiumGallery({ per_page: 24 });
  const { data: homepageData } = useHomepage();
  const homepageSections = homepageData?.page?.sections ?? [];
  const heroSection = homepageSections.find((section) => section.key === "hero_main");
  const { pathways, intro } = useSchoolPathways();
  const editor = useLiveEditorOptional();

  const brandWithOverrides = useMemo(() => {
    const base = {
      journey_milestone: enrichBrandItemsWithMedia(brand.items("journey_milestone"), premiumMedia),
      cocurricular: enrichBrandItemsWithMedia(brand.items("cocurricular"), premiumMedia),
    };
    return applyBrandOverrides(base, editor?.overrides ?? EMPTY_OVERRIDES);
  }, [brand, premiumMedia, editor?.overrides]);

  const journeyMilestones = brandWithOverrides.journey_milestone || [];
  const cocurricular = brandWithOverrides.cocurricular || [];

  return (
    <SiteShell>
      <HeroSection settings={settings} heroMedia={heroMedia ?? undefined} cmsHero={heroSection} />

      {/* Crawlable programme links — present even when CMS hooks are empty */}
      <section className="border-b border-rk-border bg-rk-white py-rk-8" aria-label="School programmes">
        <div className="rk-container">
          <h2 className="sr-only">Programmes at Royal Kings Premier School</h2>
          <ul className="flex flex-wrap justify-center gap-3 text-sm font-semibold">
            <li>
              <Link href="/preschool-wangige" className="rounded-rk-pill bg-rk-soft-lavender px-4 py-2 text-rk-deep-purple hover:bg-rk-gold">
                <EditableText editKey="home.pill.preschool" as="span">
                  Preschool in Wangige
                </EditableText>
              </Link>
            </li>
            <li>
              <Link href="/primary-school-wangige" className="rounded-rk-pill bg-rk-soft-lavender px-4 py-2 text-rk-deep-purple hover:bg-rk-gold">
                <EditableText editKey="home.pill.primary" as="span">
                  Primary School
                </EditableText>
              </Link>
            </li>
            <li>
              <Link href="/junior-school-wangige" className="rounded-rk-pill bg-rk-soft-lavender px-4 py-2 text-rk-deep-purple hover:bg-rk-gold">
                <EditableText editKey="home.pill.junior" as="span">
                  Junior School
                </EditableText>
              </Link>
            </li>
            <li>
              <Link href="/admissions" className="rounded-rk-pill bg-rk-purple px-4 py-2 text-white hover:bg-rk-deep-purple">
                <EditableText editKey="home.pill.admissions" as="span">
                  Admissions
                </EditableText>
              </Link>
            </li>
          </ul>
        </div>
      </section>

      <HomePromos sections={homepageSections} />
      <SchoolStoryFromCms sections={homepageSections} />
      <FindYourPlaceSection pathways={pathways} subtitle={intro.subtitle} />
      <OneJourney milestones={journeyMilestones} />
      <BeyondClassroom items={cocurricular} />
      <VideoMoments limit={24} />
      <TestimonialsCarousel testimonials={testimonials.data || []} />
      <LatestEvents events={events.data || []} />
    </SiteShell>
  );
}
