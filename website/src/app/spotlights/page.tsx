"use client";

import { useQuery } from "@tanstack/react-query";
import { RichPage, PageHero, SectionBlock } from "@/components/layout/RichPage";
import { HIGHLIGHTS } from "@/content/schoolContent";
import { enterpriseService } from "@/services/enterpriseService";

const FALLBACK_SPOTLIGHTS = [
  { id: 1, title: "November Talent Camp", achievement: "Creative excellence", story: "Our annual holiday programme showcases music, drama, and talent across all age groups." },
  { id: 2, title: "Sports & Co-curricular", achievement: "Team spirit", story: "Learners compete and collaborate in archery, skating, ballet, coding, and more." },
  { id: 3, title: "Academic Growth", achievement: "CBC mastery", story: "Dedicated teachers guide every child from Creche through Grade 9 with care and rigour." },
];

export default function SpotlightsPage() {
  const { data, isLoading } = useQuery({ queryKey: ["showcase"], queryFn: enterpriseService.showcase });
  const showcase = data?.data;
  const spotlights = showcase?.spotlights?.length ? showcase.spotlights : FALLBACK_SPOTLIGHTS;

  return (
    <RichPage>
      <PageHero title="Student Life & Achievements" subtitle="Celebrating excellence across sports, music, academics, and character at Royal Kings." />
      <SectionBlock>
        {isLoading && <p>Loading spotlights...</p>}
        <div className="grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
          {spotlights.map((s: { id: number; title: string; story?: string; achievement?: string; student?: string }) => (
            <article key={s.id} className="rounded-2xl border border-[var(--rk-border)] bg-white p-5 shadow-sm sm:p-6">
              <h2 className="font-serif text-lg font-bold text-[var(--rk-purple)] sm:text-xl">{s.title}</h2>
              {s.student && <p className="mt-1 text-sm text-[var(--rk-gold)]">{s.student}</p>}
              {s.achievement && <p className="mt-2 font-medium text-[var(--rk-purple-dark)]">{s.achievement}</p>}
              {s.story && <p className="mt-2 text-sm text-[var(--rk-muted)]">{s.story}</p>}
            </article>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock title="School Highlights" alt>
        <div className="grid gap-4 sm:grid-cols-3">
          {HIGHLIGHTS.map((h) => (
            <div key={h.title} className="rounded-xl bg-white p-4 text-center ring-1 ring-[var(--rk-border)]">
              <h3 className="font-semibold text-[var(--rk-purple)]">{h.title}</h3>
              <p className="mt-1 text-xs text-[var(--rk-muted)]">{h.subtitle}</p>
            </div>
          ))}
        </div>
      </SectionBlock>
    </RichPage>
  );
}
