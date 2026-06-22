"use client";

import { useRef } from "react";
import type { BrandItem } from "@/types/brand";

export function OneJourney({ milestones }: { milestones: BrandItem[] }) {
  const scrollRef = useRef<HTMLDivElement>(null);
  if (!milestones.length) return null;

  return (
    <section className="bg-white py-16 sm:py-20">
      <div className="mx-auto max-w-6xl px-4 lg:px-8">
        <h2 className="text-center font-serif text-3xl font-bold text-[var(--rk-purple-dark)]">One Journey. One Home.</h2>
        <p className="mx-auto mt-3 max-w-2xl text-center text-[var(--rk-muted)]">From age 3 to Grade 9 — growing in faith, confidence, and excellence at Royal Kings Premier School.</p>
        <div ref={scrollRef} className="mt-10 flex gap-4 overflow-x-auto pb-4 scrollbar-hide snap-x snap-mandatory">
          {milestones.map((m, i) => (
            <article
              key={m.title}
              className="min-w-[260px] shrink-0 snap-center overflow-hidden rounded-2xl bg-[var(--rk-surface)] ring-1 ring-[var(--rk-border)] sm:min-w-[280px]"
            >
              {m.image_url && (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={m.image_url} alt={m.title} className="h-36 w-full object-cover" />
              )}
              <div className="p-5">
                <span className="text-xs font-bold uppercase tracking-wider text-[var(--rk-gold)]">Step {i + 1}</span>
                <h3 className="mt-1 font-serif text-lg font-bold text-[var(--rk-purple)]">{m.title}</h3>
                {m.subtitle && <p className="text-sm font-medium text-[var(--rk-purple-mid)]">{m.subtitle}</p>}
                <p className="mt-2 text-sm text-[var(--rk-muted)]">{m.body}</p>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
