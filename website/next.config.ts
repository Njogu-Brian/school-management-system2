import type { NextConfig } from "next";
import path from "node:path";
import { fileURLToPath } from "node:url";

const websiteRoot = path.dirname(fileURLToPath(import.meta.url));
// Read at build time from .env.production / shell — NEXT_PUBLIC_* alone can load too late for next.config
const basePath = (process.env.WEBSITE_BASE_PATH ?? process.env.NEXT_PUBLIC_BASE_PATH ?? "").replace(/\/$/, "");

const nextConfig: NextConfig = {
  // Prevent Next from using the Laravel repo root when multiple lockfiles exist
  outputFileTracingRoot: websiteRoot,
  turbopack: {
    root: websiteRoot,
  },
  basePath: basePath || undefined,
  images: {
    remotePatterns: [
      { protocol: "http", hostname: "127.0.0.1" },
      { protocol: "http", hostname: "localhost" },
      { protocol: "https", hostname: "**" },
    ],
  },
  async redirects() {
    return [
      { source: "/about-us", destination: "/about", permanent: true },
      { source: "/school-fees", destination: "/fees", permanent: true },
      { source: "/co-curricular-activities", destination: "/co-curricular", permanent: true },
      { source: "/school-transport", destination: "/transport", permanent: true },
      { source: "/news", destination: "/blog", permanent: true },
      { source: "/contact-us", destination: "/contact", permanent: true },
      { source: "/kindergarten-wangige", destination: "/preschool-wangige", permanent: true },
      { source: "/nursery-school-wangige", destination: "/preschool-wangige", permanent: true },
    ];
  },
  async headers() {
    return [
      {
        // Avoid year-long CDN/browser caching of HTML after deploys
        source: "/:path*",
        headers: [
          { key: "Cache-Control", value: "public, max-age=0, s-maxage=60, stale-while-revalidate=300" },
        ],
      },
      {
        source: "/_next/static/:path*",
        headers: [
          { key: "Cache-Control", value: "public, max-age=31536000, immutable" },
        ],
      },
    ];
  },
};

export default nextConfig;
