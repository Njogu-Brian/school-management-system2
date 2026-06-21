"use client";

import { RichPage, PageHero, SectionBlock } from "@/components/layout/RichPage";
import { HIGHLIGHTS } from "@/content/schoolContent";
import { useEvents } from "@/hooks/useWebsiteData";
import Link from "next/link";

export default function EventsPage() {
  const { data, isLoading } = useEvents();

  return (
    <RichPage>
      <PageHero title="Events" subtitle="Open days, talent camps, sports days, and family celebrations at Royal Kings." />
      <SectionBlock title="Upcoming Highlights">
        <div className="grid gap-4 sm:grid-cols-2">
          {HIGHLIGHTS.map((h) => (
            <article key={h.title} className="rounded-2xl bg-[var(--rk-surface)] p-5 sm:p-6">
              <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">{h.title}</h3>
              <p className="mt-2 text-sm text-[var(--rk-muted)]">{h.subtitle}</p>
            </article>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock title="School Calendar" alt>
        {isLoading && <p>Loading events...</p>}
        <div className="space-y-4">
          {(data || []).length === 0 && !isLoading && (
            <p className="text-center text-[var(--rk-muted)]">More events coming soon. Contact us about Open Day and November Talent Camp.</p>
          )}
          {(data || []).map((event) => (
            <article key={`${event.source}-${event.id}`} className="rounded-2xl border border-[var(--rk-border)] bg-white p-5 sm:p-6">
              <h2 className="font-serif text-xl font-bold text-[var(--rk-purple-dark)]">{event.title}</h2>
              <p className="mt-2 text-sm text-[var(--rk-muted)]">{event.start_date} · {event.location}</p>
              <p className="mt-3 text-sm text-[var(--rk-muted)] sm:text-base">{event.description}</p>
            </article>
          ))}
        </div>
        <div className="mt-8 text-center">
          <Link href="/admissions" className="rounded-full bg-[var(--rk-purple)] px-6 py-3 text-sm font-semibold text-white">
            RSVP / Enquire
          </Link>
        </div>
      </SectionBlock>
    </RichPage>
  );
}
