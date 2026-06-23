"use client";

import Link from "next/link";
import { ResponsiveImage } from "@/components/media/ResponsiveImage";
import { motion, useReducedMotion } from "framer-motion";
import { heroReveal } from "@/animations/variants";
import {
  FIND_YOUR_PLACE_TITLE,
  type SchoolPathway,
} from "@/content/schoolPathways";

export function FindYourPlaceSection({
  pathways,
  subtitle,
}: {
  pathways: SchoolPathway[];
  subtitle?: string;
}) {
  const reduceMotion = useReducedMotion();

  if (!pathways.length) return null;

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
      id="find-your-place"
      className="rk-section rk-section-cream"
      aria-labelledby="find-your-place-heading"
    >
      <div className="rk-container">
        <header className="mx-auto max-w-2xl text-center">
          <motion.h2 {...reveal(0)} id="find-your-place-heading" className="rk-h2">
            {FIND_YOUR_PLACE_TITLE}
          </motion.h2>
          <motion.p {...reveal(1)} className="rk-lead mt-rk-4">
            {subtitle}
          </motion.p>
        </header>

        <div className="mt-rk-12 grid gap-rk-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-rk-8">
          {pathways.map((pathway, i) => (
            <motion.article
              key={pathway.id ?? pathway.title}
              {...reveal(i + 2)}
              className="rk-school-card rk-school-card--pathway group"
            >
              {pathway.imageUrl && (
                <div className="rk-school-card__media-wrap">
                  <ResponsiveImage
                    src={pathway.imageUrl}
                    srcSet={pathway.srcset}
                    alt={pathway.title}
                    className="rk-school-card__media"
                    sizes="(max-width: 768px) 100vw, 33vw"
                  />
                </div>
              )}

              <div className="rk-school-card__body">
                <h3 className="rk-school-card__title">{pathway.title}</h3>
                {pathway.subtitle && (
                  <p className="rk-school-card__subtitle">{pathway.subtitle}</p>
                )}
                {pathway.body && <p className="rk-school-card__text">{pathway.body}</p>}
                <Link
                  href={pathway.linkUrl}
                  className="rk-btn rk-btn-tertiary rk-school-card__explore cursor-pointer"
                >
                  {pathway.ctaLabel}
                </Link>
              </div>
            </motion.article>
          ))}
        </div>
      </div>
    </section>
  );
}
