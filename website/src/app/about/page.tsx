import { CmsPageView } from "@/components/cms/CmsPageView";
import { AboutPageStatic } from "./AboutPageStatic";

export default function AboutPage() {
  return <CmsPageView slug="about" fallback={<AboutPageStatic />} />;
}
