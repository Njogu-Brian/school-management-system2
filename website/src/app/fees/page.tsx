import type { Metadata } from "next";
import { CmsPageView } from "@/components/cms/CmsPageView";
import { pageMetadata } from "@/lib/seo";
import { FeesPageStatic } from "./FeesPageStatic";

export const metadata: Metadata = pageMetadata("/fees");

export default function FeesPage() {
  return <CmsPageView slug="fees" fallback={<FeesPageStatic />} />;
}
