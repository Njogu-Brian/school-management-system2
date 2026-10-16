import type { Metadata } from "next";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata("/leadership");

export default function LeadershipLayout({ children }: { children: React.ReactNode }) {
  return children;
}
