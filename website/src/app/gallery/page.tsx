import { CmsPageView } from "@/components/cms/CmsPageView";
import { GalleryPageStatic } from "./GalleryPageStatic";

export default function GalleryPage() {
  return <CmsPageView slug="gallery" fallback={<GalleryPageStatic />} />;
}
