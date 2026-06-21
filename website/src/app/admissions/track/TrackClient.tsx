"use client";

import { useSearchParams } from "next/navigation";
import { useState } from "react";
import { SiteShell } from "@/components/layout/SiteShell";
import { admissionService } from "@/services/admissionService";

export default function TrackApplicationClient() {
  const params = useSearchParams();
  const [no, setNo] = useState(params.get("no") || "");
  const [result, setResult] = useState<Awaited<ReturnType<typeof admissionService.track>> | null>(null);

  async function lookup() {
    setResult(await admissionService.track(no));
  }

  return (
    <SiteShell>
      <section className="mx-auto max-w-lg px-4 py-16">
        <h1 className="font-serif text-3xl font-bold text-[#2a1145]">Track Application</h1>
        <div className="mt-6 flex gap-2">
          <input className="flex-1 rounded-xl border px-4 py-3" placeholder="APP-2026-00001" value={no} onChange={(e) => setNo(e.target.value)} />
          <button type="button" onClick={lookup} className="rounded-full bg-[#5B2C8E] px-6 py-3 text-white">Track</button>
        </div>
        {result && (
          <div className="mt-8 rounded-2xl border border-[#e8dff5] bg-white p-6 shadow">
            <p><strong>Application:</strong> {result.application_no}</p>
            <p><strong>Child:</strong> {result.child_name}</p>
            <p><strong>Status:</strong> <span className="text-[#5B2C8E] capitalize">{result.status.replace("_", " ")}</span></p>
          </div>
        )}
      </section>
    </SiteShell>
  );
}
