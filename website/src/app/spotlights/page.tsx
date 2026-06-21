"use client";

import { useQuery } from "@tanstack/react-query";
import { SiteShell } from "@/components/layout/SiteShell";
import { enterpriseService } from "@/services/enterpriseService";

export default function SpotlightsPage() {
  const { data, isLoading } = useQuery({ queryKey: ["showcase"], queryFn: enterpriseService.showcase });
  const showcase = data?.data;

  return (
    <SiteShell>
      <div className="mx-auto max-w-6xl px-4 py-16">
        <h1 className="font-serif text-4xl text-[#2a1145]">Student Life & Achievements</h1>
        <p className="mt-2 text-[#4a3a5c]">Celebrating excellence across sports, music, academics & more</p>
        {isLoading && <p className="mt-8">Loading...</p>}
        <div className="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {(showcase?.spotlights || []).map((s: { id: number; title: string; story?: string; achievement?: string; student?: string }) => (
            <article key={s.id} className="rounded-2xl border border-[#e0d4f0] bg-white p-6 shadow-sm">
              <h2 className="font-serif text-xl text-[#5B2C8E]">{s.title}</h2>
              {s.student && <p className="mt-1 text-sm text-[#D4AF37]">{s.student}</p>}
              {s.achievement && <p className="mt-2 font-medium text-[#2a1145]">{s.achievement}</p>}
              {s.story && <p className="mt-2 text-sm text-[#4a3a5c]">{s.story}</p>}
            </article>
          ))}
        </div>
        {(showcase?.competitions?.length ?? 0) > 0 && (
          <section className="mt-16">
            <h2 className="font-serif text-2xl text-[#2a1145]">Competitions & Events</h2>
            <ul className="mt-6 space-y-4">
              {showcase.competitions.map((c: { id: number; title: string; description?: string; date?: string; location?: string }) => (
                <li key={c.id} className="rounded-xl bg-[#faf6ef] p-4">
                  <strong>{c.title}</strong>
                  {c.date && <span className="ml-2 text-sm text-[#5B2C8E]">{c.date}</span>}
                  {c.description && <p className="mt-1 text-sm text-[#4a3a5c]">{c.description}</p>}
                </li>
              ))}
            </ul>
          </section>
        )}
      </div>
    </SiteShell>
  );
}
