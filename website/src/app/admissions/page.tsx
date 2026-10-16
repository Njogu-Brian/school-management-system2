import type { Metadata } from "next";
import { CmsPageView } from "@/components/cms/CmsPageView";
import { pageMetadata } from "@/lib/seo";
import { AdmissionsPageStatic } from "./AdmissionsPageStatic";

export const metadata: Metadata = pageMetadata("/admissions");

export default function AdmissionsPage() {
  return <CmsPageView slug="admissions" fallback={<AdmissionsPageStatic />} />;
}
