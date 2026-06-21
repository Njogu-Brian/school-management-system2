import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Set NEXT_PUBLIC_BASE_PATH=/website when hosting under yourdomain.com/website
  basePath: process.env.NEXT_PUBLIC_BASE_PATH || "",
  images: {
    remotePatterns: [
      { protocol: "http", hostname: "127.0.0.1" },
      { protocol: "http", hostname: "localhost" },
      { protocol: "https", hostname: "**" },
    ],
  },
};

export default nextConfig;
