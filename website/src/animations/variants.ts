export const fadeUp = {
  hidden: { opacity: 0, y: 32 },
  visible: (i = 0) => ({
    opacity: 1,
    y: 0,
    transition: { duration: 0.6, delay: i * 0.08, ease: [0.22, 1, 0.36, 1] as const },
  }),
};

/** Subtle blur + lift — editorial hero entrances */
export const heroReveal = {
  hidden: { opacity: 0, y: 18, filter: "blur(6px)" },
  visible: (i = 0) => ({
    opacity: 1,
    y: 0,
    filter: "blur(0px)",
    transition: {
      duration: 0.75,
      delay: 0.15 + i * 0.09,
      ease: [0.22, 1, 0.36, 1] as const,
    },
  }),
};

export const heroPillReveal = {
  hidden: { opacity: 0, y: 10, scale: 0.96 },
  visible: (i = 0) => ({
    opacity: 1,
    y: 0,
    scale: 1,
    transition: {
      duration: 0.55,
      delay: 0.55 + i * 0.07,
      ease: [0.22, 1, 0.36, 1] as const,
    },
  }),
};

export const heroMediaReveal = {
  hidden: { scale: 1.06, opacity: 0.85 },
  visible: {
    scale: 1,
    opacity: 1,
    transition: { duration: 1.4, ease: [0.22, 1, 0.36, 1] as const },
  },
};

export const fadeIn = {
  hidden: { opacity: 0 },
  visible: { opacity: 1, transition: { duration: 0.5 } },
};

export const staggerContainer = {
  hidden: {},
  visible: { transition: { staggerChildren: 0.1 } },
};

export const scaleIn = {
  hidden: { opacity: 0, scale: 0.92 },
  visible: { opacity: 1, scale: 1, transition: { duration: 0.5 } },
};
