"use client";

import { motion } from "framer-motion";
import Link from "next/link";
import type { WebsiteSettings } from "@/types/website";
import { fadeUp } from "@/animations/variants";

export function HeroSection({ settings }: { settings?: WebsiteSettings }) {
  return (
    <section className="relative flex min-h-screen items-center justify-center overflow-hidden">
      {settings?.hero_video ? (
        <video autoPlay muted loop playsInline className="absolute inset-0 h-full w-full object-cover">
          <source src={settings.hero_video} type="video/mp4" />
        </video>
      ) : (
        <div className="absolute inset-0 bg-gradient-to-br from-[#5B2C8E] via-[#3d1d61] to-[#1a0a2e]" />
      )}
      <div className="absolute inset-0 bg-[#2a1145]/60" />
      <div className="relative z-10 mx-auto max-w-4xl px-4 text-center text-white">
        <motion.p initial="hidden" animate="visible" variants={fadeUp} custom={0} className="mb-4 text-sm uppercase tracking-[0.3em] text-[#D4AF37]">
          Creche to Grade 9 · Christian-Centered · Family-Friendly
        </motion.p>
        <motion.h1 initial="hidden" animate="visible" variants={fadeUp} custom={1} className="font-serif text-4xl font-bold leading-tight md:text-6xl">
          Where Little Steps Grow Into Great Futures
        </motion.h1>
        <motion.p initial="hidden" animate="visible" variants={fadeUp} custom={2} className="mx-auto mt-6 max-w-2xl text-lg text-white/85">
          {settings?.tagline || "Nurturing learners from age 3 through Grade 9 in a warm, premium, faith-filled community."}
        </motion.p>
        <motion.div initial="hidden" animate="visible" variants={fadeUp} custom={3} className="mt-10 flex flex-wrap justify-center gap-4">
          <Link href="/admissions" className="rounded-full bg-[#D4AF37] px-8 py-3 font-semibold text-[#2a1145] transition hover:brightness-110">
            Start Admissions
          </Link>
          <Link href="/about" className="rounded-full border border-white/40 px-8 py-3 font-semibold text-white transition hover:bg-white/10">
            Discover Royal Kings
          </Link>
        </motion.div>
        {settings?.admissions_open && (
          <motion.span initial="hidden" animate="visible" variants={fadeUp} custom={4} className="mt-8 inline-block rounded-full bg-white/15 px-4 py-2 text-sm backdrop-blur">
            ✨ Admissions Now Open {settings.current_term ? `· ${settings.current_term}` : ""}
          </motion.span>
        )}
      </div>
    </section>
  );
}
