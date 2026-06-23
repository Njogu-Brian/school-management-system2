"use client";

import type { ReactNode } from "react";
import { RichPage } from "@/components/layout/RichPage";
import { PageSectionRenderer } from "@/components/cms/PageSectionRenderer";
import { useWebsitePage } from "@/hooks/useWebsiteData";

export function CmsPageView({
  slug,
  fallback,
}: {
  slug: string;
  fallback: ReactNode;
}) {
  const { data, isLoading, isError } = useWebsitePage(slug);
  const sections = data?.sections?.filter((s) => s.type !== "page_hero") ?? [];
  const hero = data?.sections?.find((s) => s.type === "page_hero");
  const hasCms = !isError && (data?.sections?.length ?? 0) > 0;

  if (isLoading) {
    return (
      <RichPage>
        <div className="rk-container py-20 text-center text-[var(--rk-muted)]">Loading…</div>
      </RichPage>
    );
  }

  if (!hasCms) {
    return <>{fallback}</>;
  }

  return (
    <RichPage>
      {hero && <PageSectionRenderer section={hero} index={0} />}
      {sections.map((section, i) => (
        <PageSectionRenderer key={section.key} section={section} index={i + 1} />
      ))}
    </RichPage>
  );
}
