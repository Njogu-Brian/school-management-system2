import { CmsPageView } from "@/components/cms/CmsPageView";
import { FeesPageStatic } from "./FeesPageStatic";

export default function FeesPage() {
  return <CmsPageView slug="fees" fallback={<FeesPageStatic />} />;
}
