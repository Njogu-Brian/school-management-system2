import type { Metadata } from "next";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata("/blog");

export default function BlogLayout({ children }: { children: React.ReactNode }) {
  return children;
}
