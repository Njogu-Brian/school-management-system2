import type { Metadata } from "next";
import { HomePageClient } from "./HomePageClient";
import { JsonLd } from "@/components/seo/JsonLd";
import { pageMetadata, faqJsonLd } from "@/lib/seo";
import { PARENT_INFO_FAQS } from "@/content/seoLandingContent";

export const metadata: Metadata = pageMetadata("/");

export default function HomePage() {
  return (
    <>
      <JsonLd data={faqJsonLd(PARENT_INFO_FAQS.slice(0, 5))} />
      <HomePageClient />
    </>
  );
}
