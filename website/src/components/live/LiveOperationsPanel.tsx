"use client";

import { useQuery } from "@tanstack/react-query";
import { enterpriseService } from "@/services/enterpriseService";

export function LiveOperationsPanel() {
  const { data } = useQuery({ queryKey: ["live-ops"], queryFn: enterpriseService.live, staleTime: 120_000 });
  const live = data?.data;

  if (!live) return null;

  const status = live.school_status;

  return (
    <section className="bg-gradient-to-b from-[#faf6ef] to-white py-12">
      <div className="mx-auto max-w-6xl px-4">
        <h2 className="font-serif text-3xl text-[#2a1145]">School Today</h2>
        <p className="mt-1 text-[#4a3a5c]">Live from Royal Kings — noticeboard, meals & transport</p>
        <div className="mt-6 grid gap-4 md:grid-cols-3">
          <div className="rounded-2xl border border-[#e0d4f0] bg-white p-5 shadow-sm">
            <div className="text-xs uppercase tracking-wide text-[#5B2C8E]">Status</div>
            <div className="mt-2 text-xl font-semibold text-[#2a1145]">{status?.is_open ? "Open" : "Closed"}</div>
            <p className="mt-1 text-sm text-[#4a3a5c]">{status?.status_note}</p>
            {status?.current_term && <p className="mt-2 text-sm">Term: {status.current_term.name}</p>}
          </div>
          <div className="rounded-2xl border border-[#e0d4f0] bg-white p-5 shadow-sm md:col-span-2">
            <div className="text-xs uppercase tracking-wide text-[#5B2C8E]">Noticeboard</div>
            <ul className="mt-3 space-y-2 text-sm">
              {(live.noticeboard || []).slice(0, 4).map((n: { id: number; title: string }) => (
                <li key={n.id} className="border-b border-[#f0e8f8] pb-2 text-[#2a1145]">{n.title}</li>
              ))}
            </ul>
          </div>
        </div>
        {live.meals?.length > 0 && (
          <div className="mt-4 rounded-2xl border border-[#e0d4f0] bg-white p-5">
            <div className="text-xs uppercase tracking-wide text-[#5B2C8E]">This week&apos;s lunch menu</div>
            <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
              {live.meals.map((m: { date: string; lunch?: string }) => (
                <div key={m.date} className="rounded-xl bg-[#faf6ef] p-3 text-sm">
                  <div className="font-medium text-[#5B2C8E]">{m.date}</div>
                  <div className="text-[#2a1145]">{m.lunch || "—"}</div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </section>
  );
}
