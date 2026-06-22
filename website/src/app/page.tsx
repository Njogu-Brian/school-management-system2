"use client";

import { SiteShell } from "@/components/layout/SiteShell";
import { useEvents, useTestimonials, useGallery, useWebsiteSettings } from "@/hooks/useWebsiteData";
import { useBrandContent } from "@/hooks/useBrandContent";
import { HeroSection } from "@/sections/HeroSection";
import { OurSchools } from "@/sections/brand/OurSchools";
import { DistinctiveEdge } from "@/sections/brand/DistinctiveEdge";
import { OneJourney } from "@/sections/brand/OneJourney";
import { BeyondClassroom } from "@/sections/brand/BeyondClassroom";
import { ScriptureBanner, FaithPillars } from "@/sections/brand/ChristianLayer";
import { AdmissionsBanner } from "@/sections/brand/AdmissionsBanner";
import { TestimonialsCarousel, LatestEvents } from "@/sections/HomeSections";

export default function HomePage() {
  const { data: settings } = useWebsiteSettings();
  const brand = useBrandContent();
  const events = useEvents();
  const testimonials = useTestimonials();
  const gallery = useGallery("campus");

  void gallery;

  return (
    <SiteShell>
      <HeroSection settings={settings} trustPills={brand.items("trust_pill")} />
      <ScriptureBanner items={brand.items("scripture")} />
      <OurSchools cards={brand.items("school_card")} />
      <DistinctiveEdge />
      <OneJourney milestones={brand.items("journey_milestone")} />
      <BeyondClassroom items={brand.items("cocurricular")} />
      <TestimonialsCarousel testimonials={testimonials.data || []} />
      <LatestEvents events={events.data || []} />
      <FaithPillars pillars={brand.items("faith_pillar")} />
      <AdmissionsBanner />
    </SiteShell>
  );
}
