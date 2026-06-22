"use client";

import Link from "next/link";
import { motion } from "framer-motion";
import type { BrandItem } from "@/types/brand";

export function OurSchools({ cards }: { cards: BrandItem[] }) {
  if (!cards.length) return null;

  return (
    <section className="bg-[var(--rk-cream)] py-16 sm:py-20">
      <div className="mx-auto max-w-6xl px-4 lg:px-8">
        <h2 className="text-center font-serif text-3xl font-bold text-[var(--rk-purple-dark)] sm:text-4xl">Find Your Child&apos;s Place</h2>
        <p className="mx-auto mt-3 max-w-2xl text-center text-[var(--rk-muted)]">Three pathways, one caring community — from first steps to graduation.</p>
        <div className="mt-10 grid gap-6 md:grid-cols-3">
          {cards.map((card, i) => (
            <motion.article
              key={card.title}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: i * 0.08 }}
              whileHover={{ y: -6 }}
              className="overflow-hidden rounded-2xl bg-white shadow-[var(--rk-shadow-card)] ring-1 ring-[var(--rk-border)]"
            >
              {card.image_url && (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={card.image_url} alt={card.title} className="aspect-[4/3] w-full object-cover" />
              )}
              <div className="p-6">
                <h3 className="font-serif text-xl font-bold text-[var(--rk-purple)]">{card.title}</h3>
                {card.subtitle && <p className="mt-1 text-sm font-medium text-[var(--rk-gold)]">{card.subtitle}</p>}
                <p className="mt-3 text-sm leading-relaxed text-[var(--rk-muted)]">{card.body}</p>
                <Link href={card.link_url || "/academics"} className="mt-4 inline-flex items-center gap-1 text-sm font-bold text-[var(--rk-gold)] hover:text-[var(--rk-purple)]">
                  Explore <span aria-hidden>→</span>
                </Link>
              </div>
            </motion.article>
          ))}
        </div>
      </div>
    </section>
  );
}
