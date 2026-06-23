import { CmsPageView } from "@/components/cms/CmsPageView";
import { AdmissionsPageStatic } from "./AdmissionsPageStatic";

export default function AdmissionsPage() {
  return <CmsPageView slug="admissions" fallback={<AdmissionsPageStatic />} />;
}
