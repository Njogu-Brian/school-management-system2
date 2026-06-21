"use client";

import { SiteShell } from "@/components/layout/SiteShell";
import { useWebsitePage } from "@/hooks/useWebsiteData";

export function ContentPage({ slug, fallbackTitle, fallbackBody }: { slug: string; fallbackTitle: string; fallbackBody: string }) {
  const { data, isLoading, isError } = useWebsitePage(slug);

  return (
    <SiteShell>
      <section className="bg-gradient-to-br from-[#5B2C8E] to-[#2a1145] py-16 text-white">
        <div className="mx-auto max-w-4xl px-4 text-center lg:px-8">
          <h1 className="font-serif text-4xl font-bold">{data?.title || fallbackTitle}</h1>
        </div>
      </section>
      <section className="mx-auto max-w-4xl px-4 py-16 lg:px-8">
        {isLoading && <p className="text-[#5B2C8E]">Loading...</p>}
        {isError && <div className="prose max-w-none text-[#4a3a5c]"><p>{fallbackBody}</p></div>}
        {data?.sections?.map((section) => (
          <div key={section.key} className="mb-10">
            {section.title && <h2 className="font-serif text-2xl font-bold text-[#2a1145]">{section.title}</h2>}
            {section.subtitle && <p className="mt-2 text-[#D4AF37]">{section.subtitle}</p>}
            {section.content && <div className="prose mt-4 max-w-none" dangerouslySetInnerHTML={{ __html: section.content }} />}
          </div>
        ))}
        {!data?.sections?.length && !isLoading && <p className="text-[#4a3a5c]">{fallbackBody}</p>}
      </section>
    </SiteShell>
  );
}
