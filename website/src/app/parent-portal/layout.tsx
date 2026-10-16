import type { Metadata } from "next";
import { PRIVATE_PAGE_METADATA } from "@/lib/seo";

export const metadata: Metadata = {
  ...PRIVATE_PAGE_METADATA,
  title: "Parent Portal",
};

export default function ParentPortalLayout({ children }: { children: React.ReactNode }) {
  return children;
}
