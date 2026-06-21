"use client";

import { AnimatePresence, motion } from "framer-motion";
import { useEffect, useRef, useState } from "react";
import gsap from "gsap";
import { AGE_JOURNEY } from "@/lib/age-journey";
import { fadeUp } from "@/animations/variants";

export function AgeJourneySlider() {
  const [index, setIndex] = useState(0);
  const cardRef = useRef<HTMLDivElement>(null);
  const step = AGE_JOURNEY[index];

  useEffect(() => {
    if (!cardRef.current) return;
    gsap.fromTo(cardRef.current, { opacity: 0, y: 24, scale: 0.98 }, { opacity: 1, y: 0, scale: 1, duration: 0.5, ease: "power3.out" });
  }, [index]);

  return (
    <section className="bg-gradient-to-b from-[#faf7ff] to-white py-20">
      <div className="mx-auto max-w-6xl px-4 lg:px-8">
        <motion.div initial="hidden" whileInView="visible" viewport={{ once: true }} variants={fadeUp} className="text-center">
          <p className="text-sm uppercase tracking-widest text-[var(--rk-purple)]">Signature Experience</p>
          <h2 className="mt-2 font-serif text-3xl font-bold text-[var(--rk-purple-deep)] md:text-4xl">The Age Journey: 3 to 15</h2>
          <p className="mx-auto mt-4 max-w-2xl text-[#4a3a5c]">Slide through each age to explore levels, classrooms, activities, and milestones.</p>
        </motion.div>

        <div className="mt-12">
          <input
            type="range"
            min={0}
            max={AGE_JOURNEY.length - 1}
            value={index}
            onChange={(e) => setIndex(Number(e.target.value))}
            className="h-2 w-full cursor-pointer appearance-none rounded-full bg-[#e8dff5] accent-[var(--rk-purple)]"
            aria-label="Age journey slider"
          />
          <div className="mt-2 flex justify-between text-xs text-[var(--rk-purple)]">
            <span>Age 3</span>
            <span className="font-bold text-lg">Age {step.age}</span>
            <span>Age 15</span>
          </div>
        </div>

        <AnimatePresence mode="wait">
          <motion.div key={step.age} ref={cardRef} className="mt-10 grid gap-6 rounded-3xl border border-[#e8dff5] bg-white p-8 shadow-xl md:grid-cols-2">
            <div>
              <p className="text-sm font-semibold uppercase tracking-wider text-[#D4AF37]">{step.level}</p>
              <h3 className="mt-2 font-serif text-3xl font-bold text-[var(--rk-purple-deep)]">{step.classroom}</h3>
              <p className="mt-4 text-[#4a3a5c]">At age {step.age}, learners thrive in {step.classroom} with purposeful play, faith, and academic growth.</p>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <h4 className="font-semibold text-[var(--rk-purple)]">Activities</h4>
                <ul className="mt-2 space-y-1 text-sm text-[#4a3a5c]">{step.activities.map((a) => <li key={a}>• {a}</li>)}</ul>
              </div>
              <div>
                <h4 className="font-semibold text-[var(--rk-purple)]">Milestones</h4>
                <ul className="mt-2 space-y-1 text-sm text-[#4a3a5c]">{step.milestones.map((m) => <li key={m}>• {m}</li>)}</ul>
              </div>
            </div>
          </motion.div>
        </AnimatePresence>
      </div>
    </section>
  );
}
