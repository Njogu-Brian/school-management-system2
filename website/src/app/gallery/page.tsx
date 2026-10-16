import type { Metadata } from "next";
import { CmsPageView } from "@/components/cms/CmsPageView";
import { pageMetadata } from "@/lib/seo";
import { GalleryPageStatic } from "./GalleryPageStatic";

export const metadata: Metadata = pageMetadata("/gallery");

export default function GalleryPage() {
  return <CmsPageView slug="gallery" fallback={<GalleryPageStatic />} />;
}
