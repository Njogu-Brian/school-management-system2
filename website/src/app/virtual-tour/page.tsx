"use client";

import { useEffect, useState } from "react";
import { RichPage, PageHero, SectionBlock } from "@/components/layout/RichPage";
import { CAMPUS_LIFE, LEGACY_IMAGES } from "@/content/schoolContent";
import { api } from "@/lib/api";

interface TourStop {
  id: number;
  title: string;
  description?: string;
  image?: string;
  panorama_url?: string;
}

const FALLBACK_STOPS: TourStop[] = [
  { id: 1, title: "Main Campus", description: "Welcome to Royal Kings School in Wangige — where learning is fun.", image: LEGACY_IMAGES.campus },
  { id: 2, title: "Classrooms", description: "Bright, caring spaces designed for CBC learning from Creche to Grade 9." },
  { id: 3, title: "Playground & Sports", description: "Safe outdoor areas for play, sports, and physical development." },
  { id: 4, title: "Worship & Assembly", description: "Daily devotions rooted in Christian values of kindness, respect, truth, and love." },
];

export default function VirtualTourPage() {
  const [stops, setStops] = useState<TourStop[]>(FALLBACK_STOPS);
  const [active, setActive] = useState(0);

  useEffect(() => {
    api
      .get<{ data: TourStop[] }>("/website/virtual-tour")
      .then((r) => {
        if (r.data.data?.length) setStops(r.data.data);
      })
      .catch(() => {});
  }, []);

  const stop = stops[active];

  return (
    <RichPage>
      <PageHero title="Virtual Campus Tour" subtitle={CAMPUS_LIFE.intro} image={LEGACY_IMAGES.campus} />
      <SectionBlock>
        {stop && (
          <div className="overflow-hidden rounded-3xl bg-[var(--rk-surface)] shadow-xl ring-1 ring-[var(--rk-border)]">
            {stop.panorama_url ? (
              <iframe src={stop.panorama_url} className="h-[240px] w-full sm:h-[360px] lg:h-[420px]" title={stop.title} />
            ) : stop.image ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={stop.image} alt={stop.title} className="h-56 w-full object-cover sm:h-80" />
            ) : (
              <div className="flex h-56 items-center justify-center bg-[var(--rk-border)] text-5xl sm:h-80 sm:text-6xl">🏫</div>
            )}
            <div className="p-5 sm:p-8">
              <h2 className="font-serif text-xl font-bold text-[var(--rk-purple-dark)] sm:text-2xl">{stop.title}</h2>
              <p className="mt-3 text-sm text-[var(--rk-muted)] sm:text-base">{stop.description}</p>
              <div className="mt-6 flex flex-wrap gap-3">
                <button type="button" disabled={active === 0} onClick={() => setActive(active - 1)} className="rounded-full border px-4 py-2 text-sm disabled:opacity-40 sm:px-5">
                  Previous
                </button>
                <button type="button" disabled={active >= stops.length - 1} onClick={() => setActive(active + 1)} className="rounded-full bg-[var(--rk-purple)] px-4 py-2 text-sm text-white disabled:opacity-40 sm:px-5">
                  Next Stop
                </button>
              </div>
            </div>
          </div>
        )}
      </SectionBlock>
    </RichPage>
  );
}
