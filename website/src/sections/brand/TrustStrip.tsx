"use client";

import type { BrandItem } from "@/types/brand";

export function TrustStrip({ pills }: { pills: BrandItem[] }) {
  if (!pills.length) return null;

  return (
    <section className="relative z-20 -mt-8 px-4 sm:-mt-10">
      <div className="mx-auto flex max-w-5xl flex-wrap justify-center gap-2 sm:gap-3">
        {pills.map((pill) => (
          <span
            key={pill.title}
            className="rounded-full border border-white/60 bg-white/90 px-4 py-2 text-xs font-semibold text-[var(--rk-purple-dark)] shadow-[var(--rk-shadow-soft)] backdrop-blur-md sm:text-sm"
          >
            {pill.title}
          </span>
        ))}
      </div>
    </section>
  );
}
