"use client";

import { motion, useReducedMotion } from "framer-motion";
import Link from "next/link";
import { INTRO, MISSION, PILLARS } from "@/content/schoolContent";
import { LEGACY_HEROES } from "@/content/legacyGallery";
import { fadeUp } from "@/animations/variants";

function reveal(index: number, reduceMotion: boolean | null) {
  return reduceMotion
    ? {}
    : {
        initial: "hidden" as const,
        whileInView: "visible" as const,
        viewport: { once: true, margin: "-60px" },
        variants: fadeUp,
        custom: index,
      };
}

export function SchoolStorySectionFallback() {
  const reduceMotion = useReducedMotion();

  return (
    <>
      <section className="bg-[var(--rk-cream)] py-rk-16 lg:py-rk-20">
        <div className="rk-container grid items-center gap-rk-10 lg:grid-cols-2 lg:gap-rk-16">
          <motion.div {...reveal(0, reduceMotion)}>
            <p className="rk-overline text-[var(--rk-purple)]">Since 2006</p>
            <h2 className="rk-h2 mt-rk-3 text-[var(--rk-text)]">Empowering Minds, Shaping Futures</h2>
            <div className="rk-body mt-rk-5 space-y-rk-4 text-[var(--rk-muted)]">
              <p>{INTRO.empowering}</p>
              <p>{INTRO.holistic}</p>
            </div>
            <Link href="/about" className="rk-btn-tertiary mt-rk-6 inline-flex">
              Our Story
            </Link>
          </motion.div>
          <motion.div className="relative" {...reveal(1, reduceMotion)}>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src={LEGACY_HEROES.empowering}
              alt="Royal Kings learner engaged in classroom activity"
              className="w-full rounded-rk-xl object-cover shadow-rk-lg ring-1 ring-[var(--rk-border)]"
            />
          </motion.div>
        </div>
      </section>

      <section className="bg-white py-rk-16 lg:py-rk-20">
        <div className="rk-container grid items-center gap-rk-10 lg:grid-cols-2 lg:gap-rk-16">
          <motion.div className="order-2 lg:order-1" {...reveal(0, reduceMotion)}>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src={LEGACY_HEROES.mission}
              alt="Royal Kings students learning together"
              className="w-full rounded-rk-xl object-cover shadow-rk-lg ring-1 ring-[var(--rk-border)]"
            />
          </motion.div>
          <motion.div className="order-1 lg:order-2" {...reveal(1, reduceMotion)}>
            <p className="rk-overline text-[var(--rk-purple)]">{MISSION.title}</p>
            <p className="rk-body mt-rk-4 text-[var(--rk-muted)]">{MISSION.body}</p>
            <div className="mt-rk-8 grid gap-rk-4 sm:grid-cols-2">
              {PILLARS.slice(0, 4).map((pillar) => (
                <article key={pillar.title} className="rounded-rk-lg bg-[var(--rk-cream)] p-rk-4 ring-1 ring-[var(--rk-border)]">
                  <span className="text-xl" aria-hidden>{pillar.icon}</span>
                  <h3 className="mt-rk-2 font-serif text-base font-semibold text-[var(--rk-purple)]">{pillar.title}</h3>
                  <p className="mt-rk-1 line-clamp-3 text-sm text-[var(--rk-muted)]">{pillar.description}</p>
                </article>
              ))}
            </div>
          </motion.div>
        </div>
      </section>
    </>
  );
}
