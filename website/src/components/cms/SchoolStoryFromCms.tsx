"use client";

import type { PageSection } from "@/types/website";
import { PageSectionRenderer } from "@/components/cms/PageSectionRenderer";
import { SchoolStorySectionFallback } from "@/sections/brand/SchoolStorySection";

export function SchoolStoryFromCms({ sections }: { sections: PageSection[] }) {
  const storySections = sections.filter((s) => s.type === "school_story");

  if (storySections.length === 0) {
    return <SchoolStorySectionFallback />;
  }

  return (
    <>
      {storySections.map((section, i) => (
        <PageSectionRenderer key={section.key} section={section} index={i} />
      ))}
    </>
  );
}
