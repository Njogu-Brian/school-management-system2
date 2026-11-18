"use client";

import { motion, useReducedMotion } from "framer-motion";
import type { BrandItem } from "@/types/brand";
import { heroReveal } from "@/animations/variants";
import { EditableText } from "@/components/live-editor/EditableText";
import { EditableImage } from "@/components/live-editor/EditableImage";
import { useLiveEditorOptional } from "@/components/live-editor/LiveEditorProvider";

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
  const editor = useLiveEditorOptional();

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
          <motion.div {...reveal(0)}>
            <EditableText
              editKey="beyondClassroom.title"
              as="h2"
              id="beyond-classroom-heading"
              className="rk-h2"
            >
              Beyond the Classroom
            </EditableText>
          </motion.div>
          <motion.div {...reveal(1)}>
            <EditableText
              editKey="beyondClassroom.lead"
              as="p"
              multiline
              className="rk-lead mt-rk-4"
            >
              Premium co-curricular programmes that unlock every child's gifts — on campus and beyond.
            </EditableText>
          </motion.div>
        </header>

        <div className="rk-bento-grid mt-rk-12">
          {items.map((item, i) => {
            const base = `brand.cocurricular.${i}`;
            const imageUrl = editor?.getImage(`${base}.image`, item.image_url || "") || item.image_url;
            const videoUrl = editor?.getVideo(`${base}.video`, item.video_url || "") || item.video_url;
            return (
              <motion.article
                key={item.id ?? item.title}
                {...reveal(i + 2)}
                className={`rk-bento-tile group ${tileSlot(i, item)}`}
              >
                {videoUrl && !editor?.editing ? (
                  <video
                    autoPlay
                    muted
                    loop
                    playsInline
                    className="rk-bento-tile__media"
                    poster={imageUrl}
                  >
                    <source src={videoUrl} type="video/mp4" />
                  </video>
                ) : imageUrl ? (
                  <EditableImage
                    editKey={`${base}.image`}
                    src={imageUrl}
                    alt={item.title ?? "Co-curricular programme"}
                    className="rk-bento-tile__media"
                  />
                ) : (
                  <div className="rk-bento-tile__media bg-rk-soft-lavender" />
                )}

                {editor?.editing ? (
                  <button
                    type="button"
                    className="rk-editable-image-badge"
                    style={{ top: "18%", zIndex: 8 }}
                    onClick={(e) => {
                      e.preventDefault();
                      e.stopPropagation();
                      editor.openMediaPicker({
                        key: `${base}.video`,
                        kind: "video",
                        target: "video",
                        currentUrl: videoUrl || "",
                        title: `Replace video · ${item.title || "programme"}`,
                      });
                    }}
                  >
                    {videoUrl ? "Replace video" : "Add video"}
                  </button>
                ) : null}

                <div className="rk-bento-tile__overlay" aria-hidden />
                <div className="rk-bento-tile__content">
                  <EditableText editKey={`${base}.title`} as="h3" className="rk-bento-tile__title">
                    {item.title || ""}
                  </EditableText>
                  {item.body && (
                    <EditableText
                      editKey={`${base}.body`}
                      as="p"
                      multiline
                      className="rk-bento-tile__text"
                    >
                      {item.body}
                    </EditableText>
                  )}
                </div>
              </motion.article>
            );
          })}
        </div>
      </div>
    </section>
  );
}
