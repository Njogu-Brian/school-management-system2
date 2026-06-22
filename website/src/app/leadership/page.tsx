"use client";

import { RichPage, PageHero, SectionBlock } from "@/components/layout/RichPage";
import { useBrandContent } from "@/hooks/useBrandContent";

export default function LeadershipPage() {
  const brand = useBrandContent();
  const leaders = brand.items("leader");

  return (
    <RichPage>
      <PageHero title="Our Leadership" subtitle="Faces you can trust — guiding Royal Kings Premier School with faith and excellence." />
      <SectionBlock>
        <div className="grid gap-8 md:grid-cols-3">
          {leaders.map((leader) => (
            <article key={leader.title} className="text-center">
              {leader.image_url && (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={leader.image_url} alt={leader.title} className="mx-auto h-32 w-32 rounded-full object-cover ring-4 ring-[var(--rk-gold)]" />
              )}
              <h3 className="mt-4 font-serif text-xl font-bold text-[var(--rk-purple)]">{leader.title}</h3>
              {leader.subtitle && <p className="text-sm font-medium text-[var(--rk-gold)]">{leader.subtitle}</p>}
              <p className="mt-3 text-sm text-[var(--rk-muted)]">{leader.body}</p>
            </article>
          ))}
        </div>
      </SectionBlock>
    </RichPage>
  );
}
