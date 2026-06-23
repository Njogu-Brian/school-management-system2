"use client";

import { SiteShell } from "@/components/layout/SiteShell";
import { useEvents, useTestimonials, useWebsiteSettings } from "@/hooks/useWebsiteData";
import { useHeroMedia, usePremiumGallery } from "@/hooks/usePremiumMedia";
import { useBrandContent } from "@/hooks/useBrandContent";
import { useSchoolPathways } from "@/hooks/useSchoolPathways";
import { enrichBrandItemsWithMedia } from "@/lib/premiumMedia";
import { HeroSection } from "@/sections/HeroSection";
import { SchoolStorySection } from "@/sections/brand/SchoolStorySection";
import { FindYourPlaceSection } from "@/sections/brand/FindYourPlaceSection";
import { OneJourney } from "@/sections/brand/OneJourney";
import { BeyondClassroom } from "@/sections/brand/BeyondClassroom";
import { AdmissionsBanner } from "@/sections/brand/AdmissionsBanner";
import { TestimonialsCarousel, LatestEvents } from "@/sections/HomeSections";

export default function HomePage() {
  const { data: settings } = useWebsiteSettings();
  const brand = useBrandContent();
  const events = useEvents();
  const testimonials = useTestimonials(true);
  const { data: heroMedia } = useHeroMedia();
  const { data: premiumMedia = [] } = usePremiumGallery({ per_page: 24 });
  const { pathways, intro } = useSchoolPathways();

  const journeyMilestones = enrichBrandItemsWithMedia(brand.items("journey_milestone"), premiumMedia);
  const cocurricular = enrichBrandItemsWithMedia(brand.items("cocurricular"), premiumMedia);

  return (
    <SiteShell>
      <HeroSection settings={settings} trustPills={brand.items("trust_pill")} heroMedia={heroMedia ?? undefined} />
      <SchoolStorySection />
      <FindYourPlaceSection pathways={pathways} subtitle={intro.subtitle} />
      <OneJourney milestones={journeyMilestones} />
      <BeyondClassroom items={cocurricular} />
      <TestimonialsCarousel testimonials={testimonials.data || []} />
      <LatestEvents events={events.data || []} />
      <AdmissionsBanner />
    </SiteShell>
  );
}
