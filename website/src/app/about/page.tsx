import type { Metadata } from "next";
import { CmsPageView } from "@/components/cms/CmsPageView";
import { pageMetadata } from "@/lib/seo";
import { AboutPageStatic } from "./AboutPageStatic";

export const metadata: Metadata = pageMetadata("/about");

export default function AboutPage() {
  return <CmsPageView slug="about" fallback={<AboutPageStatic />} />;
}
