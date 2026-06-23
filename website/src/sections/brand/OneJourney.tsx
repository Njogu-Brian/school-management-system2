"use client";

import { useRef } from "react";
import { motion, useReducedMotion } from "framer-motion";
import type { BrandItem } from "@/types/brand";
import { heroReveal } from "@/animations/variants";
import { ResponsiveImage } from "@/components/media/ResponsiveImage";

const SUBTITLE =
  "From first words to graduation — one caring community walking with your child every step of the way.";

export function OneJourney({ milestones }: { milestones: BrandItem[] }) {
  const scrollRef = useRef<HTMLDivElement>(null);
  const reduceMotion = useReducedMotion();

  if (!milestones.length) return null;

  const reveal = (index: number) =>
    reduceMotion
      ? {}
      : {
          initial: "hidden" as const,
          whileInView: "visible" as const,
          viewport: { once: true, margin: "-60px" },
          variants: heroReveal,
          custom: index,
        };

  return (
    <section
      id="one-journey"
      className="rk-section overflow-hidden bg-rk-white"
      aria-labelledby="one-journey-heading"
    >
      <div className="rk-container">
        <header className="mx-auto max-w-2xl text-center">
          <motion.h2 {...reveal(0)} id="one-journey-heading" className="rk-h2">
            One Journey. One Home.
          </motion.h2>
          <motion.p {...reveal(1)} className="rk-lead mt-rk-4">
            {SUBTITLE}
          </motion.p>
          <motion.div {...reveal(2)} className="mt-rk-6 inline-flex items-center gap-rk-3">
            <span className="rk-overline text-rk-purple">Age 3</span>
            <span className="h-px w-12 bg-rk-gold" aria-hidden />
            <span className="rk-overline text-rk-purple">Age 15</span>
          </motion.div>
        </header>

        <div className="relative mt-rk-12">
          {/* Gold connecting line */}
          <div
            className="pointer-events-none absolute left-0 right-0 top-[7.5rem] hidden h-0.5 bg-gradient-to-r from-transparent via-rk-gold to-transparent md:block"
            aria-hidden
          />

          <div
            ref={scrollRef}
            className="rk-journey-scroll flex gap-rk-5 overflow-x-auto pb-rk-6 pt-rk-2 scrollbar-hide snap-x snap-mandatory md:gap-rk-6"
          >
            {milestones.map((m, i) => (
              <motion.article
                key={m.id ?? m.title}
                {...reveal(i + 3)}
                className="rk-journey-card group min-w-[280px] shrink-0 snap-center sm:min-w-[300px] lg:min-w-[320px]"
              >
                {/* Purple milestone marker */}
                <div className="relative mb-rk-4 flex justify-center">
                  <span
                    className="relative z-10 flex h-4 w-4 items-center justify-center rounded-full bg-rk-purple ring-4 ring-rk-white shadow-rk-sm transition-transform duration-300 group-hover:scale-125"
                    aria-hidden
                  >
                    <span className="h-1.5 w-1.5 rounded-full bg-rk-gold" />
                  </span>
                </div>

                {m.image_url && (
                  <div className="rk-journey-card__media-wrap overflow-hidden rounded-rk-xl">
                    <ResponsiveImage
                      src={m.image_url}
                      srcSet={(m.settings?.srcset as string) || undefined}
                      alt={m.title ?? "Journey milestone"}
                      className="rk-journey-card__media h-44 w-full object-cover sm:h-48"
                    />
                  </div>
                )}

                <div className="rk-journey-card__body mt-rk-4 text-center">
                  {m.subtitle && (
                    <p className="rk-overline text-rk-gold">{m.subtitle}</p>
                  )}
                  <h3 className="mt-rk-2 font-serif text-xl font-bold text-rk-purple">
                    {m.title}
                  </h3>
                  {m.body && (
                    <p className="rk-body-sm mt-rk-3 text-balance">{m.body}</p>
                  )}
                </div>
              </motion.article>
            ))}
          </div>

          <p className="mt-rk-2 text-center text-xs text-rk-muted md:hidden">
            Swipe to explore the journey →
          </p>
        </div>
      </div>
    </section>
  );
}
