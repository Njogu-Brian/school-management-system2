"use client";

import { SiteHeader } from "@/components/layout/SiteHeader";
import { SiteFooter } from "@/components/layout/SiteFooter";
import { FloatingWhatsApp, StickyAdmissionsButton } from "@/components/layout/FloatingCTAs";
import { DynamicCtas } from "@/components/conversion/DynamicCtas";
import { ExitIntentModal } from "@/components/conversion/ExitIntentModal";
import { SchoolAssistant } from "@/components/assistant/SchoolAssistant";
import { useWebsiteSettings } from "@/hooks/useWebsiteData";

export function SiteShell({ children }: { children: React.ReactNode }) {
  const { data: settings } = useWebsiteSettings();

  return (
    <>
      <SiteHeader settings={settings} />
      <main className="flex-1">{children}</main>
      <SiteFooter settings={settings} />
      <FloatingWhatsApp settings={settings} />
      <StickyAdmissionsButton settings={settings} />
      <DynamicCtas />
      <ExitIntentModal />
      <SchoolAssistant />
    </>
  );
}
