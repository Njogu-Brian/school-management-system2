"use client";

import { SiteShell } from "@/components/layout/SiteShell";
import { useHomepage, useEvents, useTestimonials, useGallery } from "@/hooks/useWebsiteData";
import { HeroSection } from "@/sections/HeroSection";
import { AgeJourneySlider } from "@/sections/AgeJourneySlider";
import { WhyRoyalKings, LearningPathway, ProgramsGrid } from "@/sections/WhyAndPrograms";
import {
  TestimonialsCarousel,
  CampusGallery,
  LatestEvents,
  StatsCounters,
  AnnouncementsTicker,
  TransportPreview,
  ParentPortalPreview,
  AdmissionsCTA,
} from "@/sections/HomeSections";
import { useWebsiteSettings } from "@/hooks/useWebsiteData";

function HomeLoading() {
  return <div className="flex min-h-[50vh] items-center justify-center text-[#5B2C8E]">Loading Royal Kings experience...</div>;
}

function HomeError() {
  return (
    <div className="mx-auto max-w-lg px-4 py-20 text-center">
      <h2 className="font-serif text-2xl text-[#2a1145]">We&apos;re preparing something beautiful</h2>
      <p className="mt-4 text-[#4a3a5c]">Connect the Laravel API and run migrations to load live homepage content. Static sections still render below.</p>
    </div>
  );
}

export default function HomePage() {
  const { data: settings } = useWebsiteSettings();
  const homepage = useHomepage();
  const events = useEvents();
  const testimonials = useTestimonials();
  const gallery = useGallery("campus");

  return (
    <SiteShell>
      <HeroSection settings={settings} />
      {homepage.data && <AnnouncementsTicker announcements={homepage.data.announcements} />}
      {homepage.isLoading && <HomeLoading />}
      {homepage.isError && <HomeError />}
      {homepage.data && <StatsCounters stats={homepage.data.live_stats} />}
      <AgeJourneySlider />
      <WhyRoyalKings />
      <LearningPathway />
      <ProgramsGrid />
      <TestimonialsCarousel testimonials={testimonials.data || []} />
      <CampusGallery items={gallery.data?.data || []} />
      <LatestEvents events={events.data || []} />
      <TransportPreview />
      <ParentPortalPreview />
      <AdmissionsCTA />
    </SiteShell>
  );
}
