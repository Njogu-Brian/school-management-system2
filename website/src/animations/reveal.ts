"use client";

import { useSyncExternalStore } from "react";
import { useReducedMotion } from "framer-motion";

type ReduceMotion = boolean | null;

/**
 * Safe scroll reveal — never leave content stuck at opacity 0.
 *
 * SSR and the first client paint omit `initial: { opacity: 0 }` so hydration
 * matches and sections stay visible. Animations enable only after mount when
 * the user has not requested reduced motion.
 */
export function scrollReveal(index = 0, reduceMotion: ReduceMotion, mounted = false) {
  if (!mounted || reduceMotion) {
    return {};
  }

  return {
    // Keep opacity at 1 so content never goes blank if whileInView misses a frame
    initial: { opacity: 1, y: 18 },
    whileInView: { opacity: 1, y: 0 },
    viewport: { once: true, amount: 0.15, margin: "0px 0px -10% 0px" },
    transition: { duration: 0.55, delay: Math.min(index, 6) * 0.06, ease: [0.22, 1, 0.36, 1] as const },
  };
}

function subscribe() {
  return () => {};
}

function useIsClient() {
  return useSyncExternalStore(subscribe, () => true, () => false);
}

/** Prefer this in client components — handles mount + reduced-motion gating. */
export function useScrollReveal() {
  const reduceMotion = useReducedMotion();
  const mounted = useIsClient();

  return (index = 0) => scrollReveal(index, reduceMotion, mounted);
}
