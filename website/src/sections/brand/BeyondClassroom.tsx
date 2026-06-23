"use client";

import { motion, useReducedMotion } from "framer-motion";
import { ResponsiveImage } from "@/components/media/ResponsiveImage";
import type { BrandItem } from "@/types/brand";
import { heroReveal } from "@/animations/variants";

/** Bento layout slots for 8 co-curricular tiles */
const BENTO_SLOTS = [
  "rk-bento-tile--hero",
  "rk-bento-tile--tall",
  "rk-bento-tile--wide",
  "rk-bento-tile--standard",
  "rk-bento-tile--standard",
  "rk-bento-tile--hero-secondary",
  "rk-bento-tile--standard",
  "rk-bento-tile--standard",
] as const;

function tileSlot(index: number, item: BrandItem) {
  const size = item.settings?.size as string | undefined;
  if (size === "large" && index === 0) return BENTO_SLOTS[0];
  if (size === "large") return BENTO_SLOTS[5];
  return BENTO_SLOTS[index] ?? "rk-bento-tile--standard";
}

export function BeyondClassroom({ items }: { items: BrandItem[] }) {
  const reduceMotion = useReducedMotion();

  if (!items.length) return null;

  const reveal = (index: number) =>
    reduceMotion
      ? {}
      : {
          initial: "hidden" as const,
          whileInView: "visible" as const,
          viewport: { once: true, margin: "-40px" },
          variants: heroReveal,
          custom: index,
        };

  return (
    <section
      id="beyond-classroom"
      className="rk-section rk-section-cream"
      aria-labelledby="beyond-classroom-heading"
    >
      <div className="rk-container">
        <header className="mx-auto max-w-2xl text-center">
          <motion.h2 {...reveal(0)} id="beyond-classroom-heading" className="rk-h2">
            Beyond the Classroom
          </motion.h2>
          <motion.p {...reveal(1)} className="rk-lead mt-rk-4">
            Premium co-curricular programmes that unlock every child&apos;s gifts — on campus and beyond.
          </motion.p>
        </header>

        <div className="rk-bento-grid mt-rk-12">
          {items.map((item, i) => (
            <motion.article
              key={item.id ?? item.title}
              {...reveal(i + 2)}
              className={`rk-bento-tile group ${tileSlot(i, item)}`}
            >
              {item.video_url ? (
                <video
                  autoPlay
                  muted
                  loop
                  playsInline
                  className="rk-bento-tile__media"
                  poster={item.image_url}
                >
                  <source src={item.video_url} type="video/mp4" />
                </video>
              ) : item.image_url ? (
                <ResponsiveImage
                  src={item.image_url}
                  srcSet={(item.settings?.srcset as string) || undefined}
                  alt={item.title ?? "Co-curricular programme"}
                  className="rk-bento-tile__media"
                  sizes="(max-width: 768px) 50vw, 25vw"
                />
              ) : (
                <div className="rk-bento-tile__media bg-rk-soft-lavender" />
              )}

              <div className="rk-bento-tile__overlay" aria-hidden />
              <div className="rk-bento-tile__content">
                <h3 className="rk-bento-tile__title">{item.title}</h3>
                {item.body && (
                  <p className="rk-bento-tile__text">{item.body}</p>
                )}
              </div>
            </motion.article>
          ))}
        </div>
      </div>
    </section>
  );
}
