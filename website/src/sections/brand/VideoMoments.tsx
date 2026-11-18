"use client";

import { useEffect, useId, useMemo, useState } from "react";
import {
  CAMPUS_REELS,
  facebookEmbedUrl,
  type CampusReel,
} from "@/content/campusMedia";
import { SOCIAL } from "@/content/schoolContent";
import { EditableText } from "@/components/live-editor/EditableText";
import { EditableImage } from "@/components/live-editor/EditableImage";
import { useLiveEditorOptional } from "@/components/live-editor/LiveEditorProvider";
import { applyCampusReelOverrides } from "@/lib/liveEditor/applyOverrides";
import { EMPTY_OVERRIDES } from "@/lib/liveEditor/types";

const FILTERS = [
  { key: "all", label: "All" },
  { key: "events", label: "Events" },
  { key: "sports", label: "Sports" },
  { key: "classroom", label: "Learning" },
  { key: "campus-life", label: "Campus life" },
] as const;

type FilterKey = (typeof FILTERS)[number]["key"];

function matchesFilter(reel: CampusReel, filter: FilterKey) {
  if (filter === "all") return true;
  if (filter === "sports") {
    return (
      reel.category === "sports" ||
      reel.category === "skating" ||
      reel.category === "swimming" ||
      reel.category === "ballet"
    );
  }
  if (filter === "events") return reel.category === "events" || reel.category === "graduation";
  return reel.category === filter;
}

export function VideoMoments({ limit = 24 }: { limit?: number }) {
  const titleId = useId();
  const editor = useLiveEditorOptional();
  const [filter, setFilter] = useState<FilterKey>("all");
  const [active, setActive] = useState<CampusReel | null>(null);

  const allReels = useMemo(
    () => applyCampusReelOverrides(CAMPUS_REELS, editor?.overrides ?? EMPTY_OVERRIDES),
    [editor?.overrides],
  );
  const reels = allReels.filter((r) => matchesFilter(r, filter)).slice(0, limit);

  useEffect(() => {
    if (!active) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setActive(null);
    };
    document.addEventListener("keydown", onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", onKey);
      document.body.style.overflow = prev;
    };
  }, [active]);

  return (
    <section className="rk-section bg-rk-black text-white" aria-labelledby={titleId}>
      <div className="rk-container">
        <header className="mx-auto max-w-2xl text-center">
          <EditableText
            editKey="videoMoments.overline"
            as="p"
            className="rk-overline rk-overline-on-dark justify-center"
          >
            Watch
          </EditableText>
          <EditableText
            editKey="videoMoments.title"
            as="h2"
            id={titleId}
            className="rk-h2 mt-rk-3 text-white"
          >
            Campus in Motion
          </EditableText>
          <EditableText
            editKey="videoMoments.lead"
            as="p"
            multiline
            className="rk-lead mt-rk-4 text-white/75"
          >
            Short Facebook Reels from Royal Kings — celebrations, co-curriculars, and everyday joy. Tap a clip to play.
          </EditableText>
        </header>

        <div className="mt-rk-8 flex flex-wrap items-center justify-center gap-2">
          {FILTERS.map((f) => (
            <button
              key={f.key}
              type="button"
              onClick={() => setFilter(f.key)}
              className={`rounded-rk-pill px-4 py-2 text-sm font-semibold transition ${
                filter === f.key
                  ? "bg-rk-gold text-rk-deep-purple"
                  : "bg-white/10 text-white/85 ring-1 ring-white/15 hover:bg-white/15"
              }`}
            >
              {f.label}
            </button>
          ))}
        </div>

        <div className="mt-rk-10 flex gap-4 overflow-x-auto pb-4 scrollbar-hide sm:grid sm:grid-cols-3 sm:overflow-visible md:grid-cols-4 lg:grid-cols-6">
          {reels.map((reel) => (
            <div
              key={reel.id}
              role="button"
              tabIndex={0}
              onClick={() => {
                if (editor?.editing) return;
                setActive(reel);
              }}
              onKeyDown={(e) => {
                if (editor?.editing) return;
                if (e.key === "Enter" || e.key === " ") {
                  e.preventDefault();
                  setActive(reel);
                }
              }}
              className="group relative w-[42vw] shrink-0 cursor-pointer snap-start overflow-hidden rounded-rk-xl bg-rk-deep-purple text-left shadow-rk-lg ring-1 ring-white/10 transition hover:-translate-y-1 hover:ring-rk-gold/60 sm:w-auto"
            >
              <div className="relative aspect-[9/16] overflow-hidden">
                <EditableImage
                  editKey={`campus.reel.${reel.id}.poster`}
                  src={reel.poster}
                  alt=""
                  className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-transparent" />
                <span className="absolute left-1/2 top-1/2 flex h-14 w-14 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-rk-gold text-rk-deep-purple shadow-rk-lg ring-4 ring-white/30">
                  <PlayIcon />
                </span>
                {editor?.editing ? (
                  <button
                    type="button"
                    className="rk-editable-image-badge"
                    style={{ top: "28%", zIndex: 8 }}
                    onClick={(e) => {
                      e.preventDefault();
                      e.stopPropagation();
                      editor.openMediaPicker({
                        key: `campus.reel.${reel.id}.video`,
                        kind: "video",
                        target: "video",
                        currentUrl: reel.facebookUrl,
                        title: `Replace reel · ${reel.title}`,
                      });
                    }}
                  >
                    Replace video
                  </button>
                ) : null}
                <div className="absolute inset-x-0 bottom-0 p-3">
                  <p className="text-xs font-bold uppercase tracking-wider text-rk-gold">
                    {reel.category.replace("-", " ")}
                  </p>
                  <EditableText
                    editKey={`campus.reel.${reel.id}.title`}
                    as="p"
                    className="mt-1 line-clamp-2 font-serif text-sm font-semibold leading-snug"
                  >
                    {reel.title}
                  </EditableText>
                </div>
              </div>
            </div>
          ))}
        </div>

        <div className="mt-rk-8 flex flex-wrap items-center justify-center gap-3 text-sm">
          <a
            href={SOCIAL.facebook}
            target="_blank"
            rel="noreferrer"
            className="rk-btn rk-btn-accent rk-btn-sm"
          >
            Follow on Facebook
          </a>
          <a
            href={SOCIAL.tiktok}
            target="_blank"
            rel="noreferrer"
            className="rounded-rk-pill border border-white/30 px-4 py-2 font-semibold text-white hover:border-rk-gold hover:text-rk-gold"
          >
            Watch on TikTok
          </a>
        </div>
      </div>

      {active ? (
        <div
          className="fixed inset-0 z-[70] flex items-center justify-center bg-black/80 p-4"
          role="dialog"
          aria-modal="true"
          aria-label={active.title}
          onClick={() => setActive(null)}
        >
          <div
            className="relative w-full max-w-md overflow-hidden rounded-rk-2xl bg-rk-deep-purple shadow-rk-xl"
            onClick={(e) => e.stopPropagation()}
          >
            <button
              type="button"
              className="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 text-white hover:bg-rk-gold hover:text-rk-deep-purple"
              onClick={() => setActive(null)}
              aria-label="Close video"
            >
              ✕
            </button>
            <div className="aspect-[9/16] w-full bg-black">
              <iframe
                title={active.title}
                src={facebookEmbedUrl(active.facebookUrl)}
                className="h-full w-full border-0"
                allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                allowFullScreen
              />
            </div>
            <div className="space-y-2 p-4">
              <h3 className="font-serif text-lg font-bold text-rk-gold">{active.title}</h3>
              <p className="text-sm text-white/80">{active.caption}</p>
              <a
                href={active.facebookUrl}
                target="_blank"
                rel="noreferrer"
                className="inline-flex text-sm font-semibold text-white underline decoration-rk-gold underline-offset-4 hover:text-rk-gold"
              >
                Open on Facebook →
              </a>
            </div>
          </div>
        </div>
      ) : null}
    </section>
  );
}

function PlayIcon() {
  return (
    <svg viewBox="0 0 24 24" className="h-6 w-6" fill="currentColor" aria-hidden>
      <path d="M8 5.5v13l11-6.5L8 5.5Z" />
    </svg>
  );
}
