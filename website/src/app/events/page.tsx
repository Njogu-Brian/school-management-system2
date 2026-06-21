"use client";

import { SiteShell } from "@/components/layout/SiteShell";
import { useEvents } from "@/hooks/useWebsiteData";

export default function EventsPage() {
  const { data, isLoading } = useEvents();

  return (
    <SiteShell>
      <section className="bg-[#5B2C8E] py-16 text-white"><div className="mx-auto max-w-6xl px-4 text-center"><h1 className="font-serif text-4xl font-bold">Events</h1></div></section>
      <section className="mx-auto max-w-4xl space-y-6 px-4 py-16">
        {isLoading && <p>Loading events...</p>}
        {(data || []).map((event) => (
          <article key={`${event.source}-${event.id}`} className="rounded-2xl border border-[#e8dff5] p-6">
            <h2 className="font-serif text-xl font-bold text-[#2a1145]">{event.title}</h2>
            <p className="mt-2 text-sm text-[#4a3a5c]">{event.start_date} · {event.location}</p>
            <p className="mt-3 text-[#4a3a5c]">{event.description}</p>
          </article>
        ))}
      </section>
    </SiteShell>
  );
}
