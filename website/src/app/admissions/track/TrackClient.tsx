"use client";

import { useSearchParams } from "next/navigation";
import { useState } from "react";
import { SiteShell } from "@/components/layout/SiteShell";
import { admissionService } from "@/services/admissionService";

type TrackResult = Awaited<ReturnType<typeof admissionService.track>> & {
  pipeline?: {
    application_status?: string;
    interview_required?: string | null;
    interview_outcome?: string | null;
    interview_fee_paid?: boolean;
    stages?: Array<{ key: string; label: string; done: boolean }>;
  } | null;
};

export default function TrackApplicationClient() {
  const params = useSearchParams();
  const [no, setNo] = useState(params.get("no") || "");
  const [result, setResult] = useState<TrackResult | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function lookup() {
    setError(null);
    try {
      const data = await admissionService.track(no);
      setResult(data as TrackResult);
    } catch (e) {
      setResult(null);
      setError((e as Error).message || "Application not found.");
    }
  }

  const stages = result?.pipeline?.stages;

  return (
    <SiteShell>
      <section className="mx-auto max-w-lg px-4 py-16">
        <h1 className="font-serif text-3xl font-bold text-[#2a1145]">Track Application</h1>
        <p className="mt-2 text-sm text-[#5a4a6a]">
          Enter the application number from your confirmation SMS or email.
        </p>
        <div className="mt-6 flex gap-2">
          <input
            className="flex-1 rounded-xl border px-4 py-3"
            placeholder="APP-2026-00001"
            value={no}
            onChange={(e) => setNo(e.target.value)}
          />
          <button
            type="button"
            onClick={lookup}
            className="rounded-full bg-[#5B2C8E] px-6 py-3 text-white"
          >
            Track
          </button>
        </div>
        {error && <p className="mt-4 text-sm text-red-600">{error}</p>}
        {result && (
          <div className="mt-8 rounded-2xl border border-[#e8dff5] bg-white p-6 shadow">
            <p>
              <strong>Application:</strong> {result.application_no}
            </p>
            <p>
              <strong>Child:</strong> {result.child_name}
            </p>
            <p>
              <strong>Status:</strong>{" "}
              <span className="text-[#5B2C8E] capitalize">
                {String(result.status || "").replace(/_/g, " ")}
              </span>
            </p>
            {result.pipeline?.interview_required && (
              <p className="mt-1 text-sm text-[#5a4a6a]">
                Interview: {String(result.pipeline.interview_required).replace(/_/g, " ")}
                {result.pipeline.interview_outcome
                  ? ` · ${result.pipeline.interview_outcome}`
                  : ""}
                {result.pipeline.interview_fee_paid ? " · fee paid" : ""}
              </p>
            )}
            {stages && stages.length > 0 && (
              <ol className="mt-6 space-y-3">
                {stages.map((stage) => (
                  <li key={stage.key} className="flex items-start gap-3 text-sm">
                    <span
                      className={`mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-semibold ${
                        stage.done
                          ? "bg-[#5B2C8E] text-white"
                          : "border border-[#d7c9e8] text-[#8a7a9a]"
                      }`}
                    >
                      {stage.done ? "✓" : ""}
                    </span>
                    <span className={stage.done ? "text-[#2a1145]" : "text-[#8a7a9a]"}>
                      {stage.label}
                    </span>
                  </li>
                ))}
              </ol>
            )}
          </div>
        )}
      </section>
    </SiteShell>
  );
}
