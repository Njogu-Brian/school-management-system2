"use client";

import { motion } from "framer-motion";
import Link from "next/link";
import type { WebsiteSettings } from "@/types/website";
import type { BrandItem } from "@/types/brand";
import { fadeUp } from "@/animations/variants";
import { BRAND, LEGACY_IMAGES } from "@/content/schoolContent";
import { TrustStrip } from "@/sections/brand/TrustStrip";

export function HeroSection({
  settings,
  trustPills = [],
}: {
  settings?: WebsiteSettings;
  trustPills?: BrandItem[];
}) {
  return (
    <>
      <section className="relative flex min-h-[80vh] items-center justify-center overflow-hidden sm:min-h-[88vh]">
        {settings?.hero_video ? (
          <video autoPlay muted loop playsInline className="absolute inset-0 h-full w-full object-cover">
            <source src={settings.hero_video} type="video/mp4" />
          </video>
        ) : (
          <div className="absolute inset-0 bg-cover bg-center" style={{ backgroundImage: `url(${LEGACY_IMAGES.campus})` }} />
        )}
        <div className="absolute inset-0 bg-gradient-to-br from-[var(--rk-purple-deep)]/75 via-[var(--rk-purple-dark)]/65 to-[var(--rk-purple)]/55" />
        <div className="relative z-10 mx-auto max-w-4xl px-4 py-16 text-center text-white sm:px-6">
          <motion.p initial="hidden" animate="visible" variants={fadeUp} custom={0} className="mb-3 text-xs uppercase tracking-[0.28em] text-[var(--rk-gold)] sm:text-sm">
            {BRAND.location} · Christian-Centered · Since {BRAND.founded}
          </motion.p>
          <motion.h1 initial="hidden" animate="visible" variants={fadeUp} custom={1} className="font-serif text-3xl font-bold leading-tight sm:text-5xl md:text-6xl">
            {BRAND.heroHeadline}
          </motion.h1>
          <motion.p initial="hidden" animate="visible" variants={fadeUp} custom={2} className="mx-auto mt-5 max-w-2xl text-base text-white/90 sm:text-lg">
            {BRAND.heroIntro}
          </motion.p>
          <motion.div initial="hidden" animate="visible" variants={fadeUp} custom={3} className="mt-8 flex flex-wrap justify-center gap-3 sm:gap-4">
            <Link href="/admissions/apply" className="rounded-full bg-[var(--rk-gold)] px-6 py-3 text-sm font-bold text-[var(--rk-purple-deep)] transition hover:brightness-110 sm:px-8">
              Apply Now
            </Link>
            <Link href="/contact" className="rounded-full border-2 border-white/50 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10 sm:px-8">
              Book a Visit
            </Link>
            <Link href="/calendar" className="rounded-full border-2 border-white/50 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10 sm:px-8">
              View Calendar
            </Link>
          </motion.div>
          {(settings?.admissions_open ?? true) && (
            <motion.span initial="hidden" animate="visible" variants={fadeUp} custom={4} className="mt-6 inline-block rounded-full bg-white/15 px-4 py-2 text-xs backdrop-blur sm:text-sm">
              2025 Admissions Open {settings?.current_term ? `· ${settings.current_term}` : ""}
            </motion.span>
          )}
        </div>
      </section>
      <TrustStrip pills={trustPills} />
    </>
  );
}
