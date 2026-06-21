"use client";

import { useEffect, useState } from "react";
import { SiteShell } from "@/components/layout/SiteShell";
import { api } from "@/lib/api";

interface TourStop {
  id: number;
  title: string;
  description?: string;
  image?: string;
  panorama_url?: string;
}

export default function VirtualTourPage() {
  const [stops, setStops] = useState<TourStop[]>([]);
  const [active, setActive] = useState(0);

  useEffect(() => {
    api.get<{ data: TourStop[] }>("/website/virtual-tour").then((r) => setStops(r.data.data)).catch(() => setStops([]));
  }, []);

  const stop = stops[active];

  return (
    <SiteShell>
      <section className="bg-[#2a1145] py-16 text-white text-center">
        <h1 className="font-serif text-4xl font-bold">Virtual Campus Tour</h1>
        <p className="mt-3 text-white/80">Walk through classrooms, worship areas, playground, and more</p>
      </section>
      <section className="mx-auto max-w-5xl px-4 py-12">
        {stop ? (
          <div className="overflow-hidden rounded-3xl bg-[#faf7ff] shadow-xl">
            {stop.panorama_url ? (
              <iframe src={stop.panorama_url} className="h-[420px] w-full" title={stop.title} />
            ) : stop.image ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={stop.image} alt={stop.title} className="h-80 w-full object-cover" />
            ) : (
              <div className="flex h-80 items-center justify-center bg-[#e8dff5] text-6xl">🏫</div>
            )}
            <div className="p-8">
              <h2 className="font-serif text-2xl font-bold text-[#2a1145]">{stop.title}</h2>
              <p className="mt-3 text-[#4a3a5c]">{stop.description}</p>
              <div className="mt-6 flex gap-3">
                <button type="button" disabled={active === 0} onClick={() => setActive(active - 1)} className="rounded-full border px-5 py-2 disabled:opacity-40">Previous</button>
                <button type="button" disabled={active >= stops.length - 1} onClick={() => setActive(active + 1)} className="rounded-full bg-[#5B2C8E] px-5 py-2 text-white disabled:opacity-40">Next Stop</button>
              </div>
            </div>
          </div>
        ) : (
          <p className="text-center text-[#4a3a5c]">Tour stops will appear here once configured in Website CMS.</p>
        )}
      </section>
    </SiteShell>
  );
}
