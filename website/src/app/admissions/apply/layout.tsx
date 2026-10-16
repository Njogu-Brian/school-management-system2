import type { Metadata } from "next";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata("/admissions/apply");

export default function ApplyLayout({ children }: { children: React.ReactNode }) {
  return children;
}
