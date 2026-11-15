"use client";

import { useMemo, useState } from "react";
import { CAMPUS_PHOTOS, type CampusPhoto } from "@/content/campusMedia";
import { EditableText } from "@/components/live-editor/EditableText";
import { EditableImage } from "@/components/live-editor/EditableImage";
import { useLiveEditorOptional } from "@/components/live-editor/LiveEditorProvider";
import { applyCampusPhotoOverrides } from "@/lib/liveEditor/applyOverrides";
import { EMPTY_OVERRIDES } from "@/lib/liveEditor/types";

const FILTERS: Array<{ key: "all" | CampusPhoto["category"]; label: string }> = [
  { key: "all", label: "All" },
  { key: "ballet", label: "Ballet" },
  { key: "skating", label: "Skating" },
  { key: "sports", label: "Sports" },
  { key: "swimming", label: "Swimming" },
  { key: "graduation", label: "Graduation" },
  { key: "classroom", label: "Classroom" },
  { key: "environment", label: "Environment" },
  { key: "chess", label: "Chess" },
  { key: "events", label: "Events" },
];

export function CampusPhotoGallery() {
  const editor = useLiveEditorOptional();
  const [filter, setFilter] = useState<(typeof FILTERS)[number]["key"]>("all");
  const photos = useMemo(
    () => applyCampusPhotoOverrides(CAMPUS_PHOTOS, editor?.overrides ?? EMPTY_OVERRIDES),
    [editor?.overrides],
  );
  const visible = filter === "all" ? photos : photos.filter((p) => p.category === filter);

  return (
    <div>
      <div className="mb-8 flex flex-wrap justify-center gap-2">
        {FILTERS.map((f) => (
          <button
            key={f.key}
            type="button"
            onClick={() => setFilter(f.key)}
            className={`rounded-rk-pill px-3 py-1.5 text-sm font-semibold transition ${
              filter === f.key
                ? "bg-rk-purple text-white"
                : "bg-rk-soft-lavender text-rk-deep-purple ring-1 ring-rk-border hover:ring-rk-gold"
            }`}
          >
            {f.label}
          </button>
        ))}
      </div>

      <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {visible.map((photo) => (
          <figure
            key={photo.id}
            className="overflow-hidden rounded-rk-xl bg-rk-white shadow-rk-soft ring-1 ring-rk-border transition hover:ring-rk-gold/60"
          >
            <EditableImage
              editKey={`campus.photo.${photo.id}.src`}
              src={photo.src}
              alt={photo.alt}
              className="aspect-[4/3] w-full object-cover"
            />
            <figcaption className="space-y-1 p-4">
              <p className="text-xs font-bold uppercase tracking-wider text-rk-gold-dark">
                {photo.category}
              </p>
              <EditableText
                editKey={`campus.photo.${photo.id}.title`}
                as="h3"
                className="font-serif text-lg font-semibold text-rk-deep-purple"
              >
                {photo.title}
              </EditableText>
              <EditableText
                editKey={`campus.photo.${photo.id}.caption`}
                as="p"
                multiline
                className="text-sm text-rk-muted"
              >
                {photo.caption}
              </EditableText>
            </figcaption>
          </figure>
        ))}
      </div>
    </div>
  );
}
