"use client";

import { motion, useReducedMotion } from "framer-motion";
import Link from "next/link";
import type { WebsiteSettings, GalleryItem } from "@/types/website";
import type { BrandItem } from "@/types/brand";
import { heroMediaReveal, heroPillReveal, heroReveal } from "@/animations/variants";
import { BRAND } from "@/content/schoolContent";
import { LEGACY_HEROES } from "@/content/legacyGallery";
import { ResponsiveImage } from "@/components/media/ResponsiveImage";
import { mediaUrl } from "@/lib/premiumMedia";

const FALLBACK_PILLS = [
  "Since 2006",
  "CBC Aligned",
  "Safe Transport",
  "Christian Values",
  "Rich Co-Curricular",
];

export function HeroSection({
  settings,
  trustPills = [],
  heroMedia,
}: {
  settings?: WebsiteSettings;
  trustPills?: BrandItem[];
  heroMedia?: GalleryItem;
}) {
  const reduceMotion = useReducedMotion();
  const pills = trustPills.length > 0 ? trustPills : FALLBACK_PILLS.map((title) => ({ title }));
  const heroImage = heroMedia ? mediaUrl(heroMedia, "xl") ?? heroMedia.url : LEGACY_HEROES.homeMural;

  const reveal = (index: number) =>
    reduceMotion
      ? {}
      : {
          initial: "hidden" as const,
          animate: "visible" as const,
          variants: heroReveal,
          custom: index,
        };

  const pillReveal = (index: number) =>
    reduceMotion
      ? {}
      : {
          initial: "hidden" as const,
          animate: "visible" as const,
          variants: heroPillReveal,
          custom: index,
        };

  return (
    <section className="relative flex min-h-[85vh] items-center justify-center overflow-hidden sm:min-h-[92vh]">
      {/* Background media */}
      <motion.div className="absolute inset-0" {...(reduceMotion ? {} : { initial: "hidden", animate: "visible", variants: heroMediaReveal })}>
        {settings?.hero_video ? (
          <video autoPlay muted loop playsInline className="absolute inset-0 h-full w-full object-cover">
            <source src={settings.hero_video} type="video/mp4" />
          </video>
        ) : (
          <ResponsiveImage
            src={heroImage}
            srcSet={heroMedia?.srcset}
            sizes="100vw"
            alt={heroMedia?.alt_text || "Royal Kings Premier School campus"}
            className="absolute inset-0 h-full w-full object-cover"
            priority
          />
        )}
      </motion.div>

      {/* Warm, subtle overlay — single tone, not heavy gradient */}
      <div
        className="absolute inset-0 bg-[var(--rk-deep-purple)]/42"
        aria-hidden
      />
      <div
        className="absolute inset-0 bg-gradient-to-t from-[var(--rk-deep-purple)]/55 via-transparent to-[var(--rk-text)]/10"
        aria-hidden
      />

      {/* Content */}
      <div className="rk-container relative z-10 py-rk-16 text-center sm:py-rk-20">
        <motion.p
          {...reveal(0)}
          className="rk-overline mb-rk-4 text-[var(--rk-gold)]"
        >
          {BRAND.location} · Christian-Centered · Since {BRAND.founded}
        </motion.p>

        <motion.h1
          {...reveal(1)}
          className="mx-auto max-w-4xl font-serif text-[clamp(2.25rem,5.5vw,4.25rem)] font-bold leading-[1.08] tracking-[-0.02em] text-white"
        >
          {BRAND.heroHeadline}
        </motion.h1>

        <motion.p
          {...reveal(2)}
          className="mx-auto mt-rk-6 max-w-2xl font-sans text-base leading-relaxed text-white/90 sm:text-lg sm:leading-[1.7]"
        >
          {BRAND.heroIntro}
        </motion.p>

        <motion.div
          {...reveal(3)}
          className="mt-rk-8 flex flex-wrap items-center justify-center gap-rk-3 sm:gap-rk-4"
        >
          <Link href="/admissions/apply" className="rk-btn rk-btn-primary rk-btn-lg cursor-pointer">
            Apply Now
          </Link>
          <Link href="/contact" className="rk-btn rk-btn-secondary-on-dark rk-btn-lg cursor-pointer">
            Book a Visit
          </Link>
          <Link href="/calendar" className="rk-btn rk-btn-secondary-on-dark rk-btn-lg cursor-pointer">
            View Calendar
          </Link>
        </motion.div>

        {(settings?.admissions_open ?? true) && (
          <motion.p
            {...reveal(4)}
            className="mt-rk-6 inline-block rounded-rk-pill border border-white/20 bg-white/10 px-rk-4 py-rk-2 text-xs font-medium text-white/90 backdrop-blur-md sm:text-sm"
          >
            2025 Admissions Open
            {settings?.current_term ? ` · ${settings.current_term}` : ""}
          </motion.p>
        )}

        {/* Trust pills — glass, inside hero */}
        <div className="mt-rk-10 flex flex-wrap items-center justify-center gap-rk-2 sm:mt-rk-12 sm:gap-rk-3">
          {pills.map((pill, i) => (
            <motion.span
              key={pill.title}
              {...pillReveal(i)}
              className="rk-trust-pill-hero cursor-default"
            >
              {pill.title}
            </motion.span>
          ))}
        </div>
      </div>
    </section>
  );
}
