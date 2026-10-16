import type { Metadata } from "next";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata("/calendar");

export default function CalendarLayout({ children }: { children: React.ReactNode }) {
  return children;
}
