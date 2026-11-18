"use client";

import Link from "next/link";
import { motion, useReducedMotion } from "framer-motion";
import { heroReveal } from "@/animations/variants";
import {
  FIND_YOUR_PLACE_TITLE,
  type SchoolPathway,
} from "@/content/schoolPathways";
import { EditableText } from "@/components/live-editor/EditableText";
import { EditableImage } from "@/components/live-editor/EditableImage";
import { useLiveEditorOptional } from "@/components/live-editor/LiveEditorProvider";
import { applyPathwayOverrides } from "@/lib/liveEditor/applyOverrides";
import { EMPTY_OVERRIDES } from "@/lib/liveEditor/types";
import { useMemo } from "react";

export function FindYourPlaceSection({
  pathways,
  subtitle,
}: {
  pathways: SchoolPathway[];
  subtitle?: string;
}) {
  const reduceMotion = useReducedMotion();
  const editor = useLiveEditorOptional();
  const resolved = useMemo(
    () => applyPathwayOverrides(pathways, editor?.overrides ?? EMPTY_OVERRIDES),
    [pathways, editor?.overrides],
  );

  if (!resolved.length) return null;

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
          <motion.div {...reveal(0)}>
            <EditableText
              editKey="findYourPlace.title"
              as="h2"
              id="find-your-place-heading"
              className="rk-h2"
            >
              {FIND_YOUR_PLACE_TITLE}
            </EditableText>
          </motion.div>
          <motion.div {...reveal(1)}>
            <EditableText
              editKey="findYourPlace.lead"
              as="p"
              multiline
              className="rk-lead mt-rk-4"
            >
              {subtitle || ""}
            </EditableText>
          </motion.div>
        </header>

        <div className="mt-rk-12 grid gap-rk-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-rk-8">
          {resolved.map((pathway, i) => {
            const id = String(pathway.id ?? i);
            const base = `pathway.${id}`;
            return (
              <motion.article
                key={pathway.id ?? pathway.title}
                {...reveal(i + 2)}
                className="rk-school-card rk-school-card--pathway group"
              >
                {pathway.imageUrl && (
                  <div className="rk-school-card__media-wrap">
                    <EditableImage
                      editKey={`${base}.image`}
                      src={pathway.imageUrl}
                      alt={pathway.title}
                      className="rk-school-card__media"
                    />
                  </div>
                )}

                <div className="rk-school-card__body">
                  <EditableText editKey={`${base}.title`} as="h3" className="rk-school-card__title">
                    {pathway.title}
                  </EditableText>
                  {pathway.subtitle && (
                    <EditableText
                      editKey={`${base}.subtitle`}
                      as="p"
                      className="rk-school-card__subtitle"
                    >
                      {pathway.subtitle}
                    </EditableText>
                  )}
                  {pathway.body && (
                    <EditableText
                      editKey={`${base}.body`}
                      as="p"
                      multiline
                      className="rk-school-card__text"
                    >
                      {pathway.body}
                    </EditableText>
                  )}
                  <Link
                    href={pathway.linkUrl}
                    className="rk-btn rk-btn-tertiary rk-school-card__explore cursor-pointer"
                  >
                    <EditableText editKey={`${base}.cta`} as="span">
                      {pathway.ctaLabel}
                    </EditableText>
                  </Link>
                </div>
              </motion.article>
            );
          })}
        </div>
      </div>
    </section>
  );
}
