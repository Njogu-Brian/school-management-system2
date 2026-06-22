"use client";

import { RichPage, PageHero, SectionBlock } from "@/components/layout/RichPage";
import { useEvents } from "@/hooks/useWebsiteData";
import Link from "next/link";

export default function CalendarPage() {
  const { data: events } = useEvents();

  return (
    <RichPage>
      <PageHero title="School Calendar" subtitle="Term dates, open days, sports days, and special events at Royal Kings Premier School." />
      <SectionBlock>
        {events && events.length > 0 ? (
          <div className="space-y-4">
            {events.map((event) => (
              <article key={`${event.source}-${event.id}`} className="flex flex-col gap-2 rounded-2xl bg-[var(--rk-cream)] p-5 ring-1 ring-[var(--rk-border)] sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">{event.title}</h3>
                  <p className="text-sm text-[var(--rk-muted)]">{event.start_date}{event.location ? ` · ${event.location}` : ""}</p>
                </div>
                <span className="text-xs font-semibold uppercase text-[var(--rk-gold)]">Upcoming</span>
              </article>
            ))}
          </div>
        ) : (
          <p className="text-center text-[var(--rk-muted)]">Calendar events will appear here. Follow us on social media for the latest updates.</p>
        )}
      </SectionBlock>
      <SectionBlock alt>
        <div className="text-center">
          <Link href="/admissions" className="rounded-full bg-[var(--rk-purple)] px-8 py-3 text-sm font-bold text-white hover:bg-[var(--rk-purple-dark)]">
            Plan Your Visit
          </Link>
        </div>
      </SectionBlock>
    </RichPage>
  );
}
