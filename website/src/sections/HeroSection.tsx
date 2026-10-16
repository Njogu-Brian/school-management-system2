"use client";

import Link from "next/link";
import type { WebsiteSettings, GalleryItem, PageSection } from "@/types/website";
import { BRAND } from "@/content/schoolContent";
import { LEGACY_HEROES } from "@/content/legacyGallery";
import { ResponsiveImage } from "@/components/media/ResponsiveImage";
import { mediaUrl } from "@/lib/premiumMedia";
import { sectionImage } from "@/lib/cmsSections";

export function HeroSection({
  settings,
  heroMedia,
  cmsHero,
}: {
  settings?: WebsiteSettings;
  heroMedia?: GalleryItem;
  cmsHero?: PageSection;
}) {
  const cmsImage = cmsHero ? sectionImage(cmsHero) : undefined;
  const heroImage = cmsImage || (heroMedia ? mediaUrl(heroMedia, "xl") ?? heroMedia.url : LEGACY_HEROES.homeMural);
  const headline = cmsHero?.title || BRAND.heroHeadline;
  const subline = cmsHero?.subtitle || BRAND.heroSubheadline;
  const intro = cmsHero?.content?.replace(/\s+/g, " ").trim() || BRAND.heroIntro;

  return (
    <section className="relative flex min-h-[85vh] items-center justify-center overflow-hidden sm:min-h-[92vh]">
      <div className="absolute inset-0 z-0">
        {settings?.hero_video ? (
          <video autoPlay muted loop playsInline className="absolute inset-0 h-full w-full object-cover">
            <source src={settings.hero_video} type="video/mp4" />
          </video>
        ) : (
          <ResponsiveImage
            src={heroImage}
            srcSet={heroMedia?.srcset}
            sizes="100vw"
            alt=""
            className="absolute inset-0 h-full w-full object-cover"
            priority
          />
        )}
      </div>

      <div className="absolute inset-0 z-[1]" style={{ background: "rgba(74, 0, 114, 0.42)" }} aria-hidden />

      <div className="rk-container relative z-10 py-rk-16 text-center sm:py-rk-20">
        <p className="rk-overline mb-rk-4 text-[var(--rk-gold)]">
          {BRAND.location} · Christian-Centered · Since {BRAND.founded}
        </p>

        <h1 className="mx-auto max-w-4xl font-serif text-[clamp(2.25rem,5.5vw,4.25rem)] font-bold leading-[1.08] tracking-[-0.02em] text-white">
          {headline}
        </h1>

        {subline ? (
          <p className="mx-auto mt-rk-4 max-w-3xl font-serif text-xl text-[var(--rk-gold)] sm:text-2xl">
            {subline}
          </p>
        ) : null}

        <p className="mx-auto mt-rk-6 max-w-2xl font-sans text-base leading-relaxed text-white/90 sm:text-lg sm:leading-[1.7]">
          {intro}
        </p>

        <div className="mt-rk-8 flex flex-wrap items-center justify-center gap-rk-3 sm:gap-rk-4">
          <Link href="/contact" className="rk-btn rk-btn-secondary-on-dark rk-btn-lg cursor-pointer">
            Book a Visit
          </Link>
        </div>
      </div>
    </section>
  );
}
