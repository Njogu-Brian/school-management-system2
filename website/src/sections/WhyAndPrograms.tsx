"use client";

import { motion } from "framer-motion";
import { WHY_ROYAL_KINGS, LEARNING_PATHWAY, PROGRAMS } from "@/lib/age-journey";
import { fadeUp, staggerContainer } from "@/animations/variants";

export function WhyRoyalKings() {
  return (
    <section className="py-20">
      <div className="mx-auto max-w-6xl px-4 lg:px-8">
        <motion.h2 initial="hidden" whileInView="visible" viewport={{ once: true }} variants={fadeUp} className="text-center font-serif text-3xl font-bold text-[#2a1145]">
          Why Royal Kings
        </motion.h2>
        <motion.div variants={staggerContainer} initial="hidden" whileInView="visible" viewport={{ once: true }} className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {WHY_ROYAL_KINGS.map((card, i) => (
            <motion.div key={card.title} variants={fadeUp} custom={i} className="rounded-2xl border border-[#e8dff5] bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
              <h3 className="font-serif text-xl font-semibold text-[#5B2C8E]">{card.title}</h3>
              <p className="mt-3 text-sm leading-relaxed text-[#4a3a5c]">{card.description}</p>
            </motion.div>
          ))}
        </motion.div>
      </div>
    </section>
  );
}

export function LearningPathway() {
  return (
    <section className="bg-[#2a1145] py-20 text-white">
      <div className="mx-auto max-w-6xl px-4 text-center lg:px-8">
        <h2 className="font-serif text-3xl font-bold">Learning Pathway</h2>
        <div className="mt-12 flex flex-wrap items-center justify-center gap-3">
          {LEARNING_PATHWAY.map((stage, i) => (
            <div key={stage} className="flex items-center gap-3">
              <span className="rounded-full bg-[#D4AF37] px-4 py-2 text-sm font-semibold text-[#2a1145]">{stage}</span>
              {i < LEARNING_PATHWAY.length - 1 && <span className="text-[#D4AF37]">→</span>}
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

export function ProgramsGrid() {
  return (
    <section className="py-20">
      <div className="mx-auto max-w-6xl px-4 lg:px-8">
        <h2 className="text-center font-serif text-3xl font-bold text-[#2a1145]">Programs & Co-Curricular</h2>
        <div className="mt-12 grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-7">
          {PROGRAMS.map((p) => (
            <div key={p.name} className="flex flex-col items-center rounded-2xl bg-[#faf7ff] p-4 text-center transition hover:bg-[#f0e8ff]">
              <span className="text-3xl">{p.icon}</span>
              <span className="mt-2 text-sm font-semibold text-[#5B2C8E]">{p.name}</span>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
