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
};

export default nextConfig;
