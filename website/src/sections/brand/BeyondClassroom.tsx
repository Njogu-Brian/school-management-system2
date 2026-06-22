"use client";

import { motion } from "framer-motion";
import type { BrandItem } from "@/types/brand";

function tileClass(size?: string) {
  if (size === "large") return "md:col-span-2 md:row-span-2 min-h-[280px]";
  return "min-h-[180px]";
}

export function BeyondClassroom({ items }: { items: BrandItem[] }) {
  if (!items.length) return null;

  return (
    <section className="bg-[var(--rk-cream)] py-16 sm:py-20">
      <div className="mx-auto max-w-6xl px-4 lg:px-8">
        <h2 className="text-center font-serif text-3xl font-bold text-[var(--rk-purple-dark)]">Beyond the Classroom</h2>
        <p className="mx-auto mt-3 max-w-2xl text-center text-[var(--rk-muted)]">Premium co-curricular programmes that unlock every child&apos;s gifts.</p>
        <div className="mt-10 grid auto-rows-fr grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
          {items.map((item, i) => {
            const size = (item.settings?.size as string) || "medium";
            const icon = (item.settings?.icon as string) || "⭐";
            return (
              <motion.article
                key={item.title}
                initial={{ opacity: 0, scale: 0.96 }}
                whileInView={{ opacity: 1, scale: 1 }}
                viewport={{ once: true }}
                transition={{ delay: i * 0.05 }}
                whileHover={{ scale: 1.02 }}
                className={`group relative overflow-hidden rounded-2xl ${tileClass(size)}`}
              >
                {item.image_url && (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={item.image_url} alt={item.title} className="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-[var(--rk-purple-deep)]/90 via-[var(--rk-purple-dark)]/50 to-transparent" />
                <div className="relative flex h-full flex-col justify-end p-5 text-white">
                  <span className="text-2xl">{icon}</span>
                  <h3 className="mt-2 font-serif text-lg font-bold">{item.title}</h3>
                  <p className="mt-1 text-sm text-white/85 line-clamp-2">{item.body}</p>
                </div>
              </motion.article>
            );
          })}
        </div>
      </div>
    </section>
  );
}
