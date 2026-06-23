import type { Config } from "tailwindcss";

/**
 * Royal Kings Premier School — Tailwind v4 theme extension.
 * Canonical tokens live in src/styles/rk-design-system.css (:root + @theme).
 * This file exposes the same scale for tooling and @config import.
 */
const config: Config = {
  content: ["./src/**/*.{js,ts,jsx,tsx,mdx}"],
  theme: {
    extend: {
      colors: {
        rk: {
          purple: "var(--rk-purple)",
          "purple-dark": "var(--rk-purple-dark)",
          "deep-purple": "var(--rk-deep-purple)",
          gold: "var(--rk-gold)",
          cream: "var(--rk-cream)",
          "soft-lavender": "var(--rk-soft-lavender)",
          white: "var(--rk-white)",
          text: "var(--rk-text)",
          muted: "var(--rk-muted)",
          border: "var(--rk-border)",
        },
      },
      fontFamily: {
        serif: ["var(--font-serif)", "ui-serif", "Georgia", "serif"],
        sans: ["var(--font-sans)", "ui-sans-serif", "system-ui", "sans-serif"],
      },
      fontSize: {
        "rk-display": ["clamp(2.5rem,5vw,4rem)", { lineHeight: "1.1", letterSpacing: "-0.02em" }],
        "rk-h1": ["clamp(2rem,4vw,3rem)", { lineHeight: "1.15", letterSpacing: "-0.02em" }],
        "rk-h2": ["clamp(1.625rem,3vw,2.25rem)", { lineHeight: "1.2", letterSpacing: "-0.01em" }],
        "rk-h3": ["clamp(1.25rem,2vw,1.5rem)", { lineHeight: "1.3" }],
        "rk-h4": ["1.125rem", { lineHeight: "1.4" }],
        "rk-lead": ["1.125rem", { lineHeight: "1.65" }],
        "rk-body": ["1rem", { lineHeight: "1.7" }],
        "rk-body-sm": ["0.875rem", { lineHeight: "1.65" }],
        "rk-caption": ["0.75rem", { lineHeight: "1.5" }],
        "rk-overline": ["0.6875rem", { lineHeight: "1.4", letterSpacing: "0.14em" }],
      },
      spacing: {
        "rk-1": "var(--rk-space-1)",
        "rk-2": "var(--rk-space-2)",
        "rk-3": "var(--rk-space-3)",
        "rk-4": "var(--rk-space-4)",
        "rk-5": "var(--rk-space-5)",
        "rk-6": "var(--rk-space-6)",
        "rk-8": "var(--rk-space-8)",
        "rk-10": "var(--rk-space-10)",
        "rk-12": "var(--rk-space-12)",
        "rk-16": "var(--rk-space-16)",
        "rk-20": "var(--rk-space-20)",
        "rk-24": "var(--rk-space-24)",
      },
      borderRadius: {
        "rk-sm": "var(--rk-radius-sm)",
        "rk-md": "var(--rk-radius-md)",
        "rk-lg": "var(--rk-radius-lg)",
        "rk-xl": "var(--rk-radius-xl)",
        "rk-2xl": "var(--rk-radius-2xl)",
        "rk-pill": "var(--rk-radius-pill)",
      },
      boxShadow: {
        "rk-xs": "var(--rk-shadow-xs)",
        "rk-sm": "var(--rk-shadow-sm)",
        "rk-md": "var(--rk-shadow-md)",
        "rk-lg": "var(--rk-shadow-lg)",
        "rk-xl": "var(--rk-shadow-xl)",
        "rk-card": "var(--rk-shadow-card)",
        "rk-soft": "var(--rk-shadow-soft)",
      },
      transitionDuration: {
        rk: "200ms",
        "rk-slow": "350ms",
      },
      maxWidth: {
        "rk-content": "72rem",
        "rk-narrow": "42rem",
        "rk-prose": "65ch",
      },
    },
  },
};

export default config;
