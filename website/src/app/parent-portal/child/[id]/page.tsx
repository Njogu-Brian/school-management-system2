"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { SiteShell } from "@/components/layout/SiteShell";
import { parentService } from "@/services/parentService";

export default function ParentChildPage() {
  const { id } = useParams<{ id: string }>();
  const studentId = Number(id);
  const [statement, setStatement] = useState<Record<string, unknown> | null>(null);
  const [attendance, setAttendance] = useState<Record<string, unknown> | null>(null);
  const [reportCards, setReportCards] = useState<unknown[]>([]);
  const [paymentLink, setPaymentLink] = useState<{ url?: string; short_url?: string } | null>(null);
  const [shareMsg, setShareMsg] = useState<string | null>(null);

  useEffect(() => {
    if (!studentId) return;
    parentService.statement(studentId).then(setStatement).catch(() => setStatement(null));
    parentService.attendance(studentId).then(setAttendance).catch(() => setAttendance(null));
    parentService.reportCards(studentId).then((r) => setReportCards(r.data || [])).catch(() => setReportCards([]));
    parentService
      .paymentLink(studentId)
      .then((r) => setPaymentLink(r.data || r))
      .catch(() => setPaymentLink(null));
  }, [studentId]);

  const sharePaymentLink = async () => {
    const url = paymentLink?.short_url || paymentLink?.url;
    if (!url) {
      setShareMsg("Payment link is not available yet.");
      return;
    }
    const message = `School fees payment link: ${url}`;
    try {
      if (typeof navigator !== "undefined" && navigator.share) {
        await navigator.share({ title: "School fees payment", text: message, url });
        setShareMsg("Shared.");
      } else if (typeof navigator !== "undefined" && navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(url);
        setShareMsg("Payment link copied to clipboard.");
      } else {
        setShareMsg(url);
      }
    } catch {
      setShareMsg(url);
    }
  };

  return (
    <SiteShell>
      <section className="mx-auto max-w-4xl px-4 py-12 space-y-8">
        <h1 className="font-serif text-3xl font-bold text-[#2a1145]">Learner Overview</h1>
        <div className="rounded-2xl border p-6">
          <h2 className="font-semibold text-[#5B2C8E]">Fee Statement</h2>
          <pre className="mt-3 overflow-auto text-xs text-[#4a3a5c]">{statement ? JSON.stringify(statement, null, 2) : "Loading..."}</pre>
          <div className="mt-4 flex flex-wrap items-center gap-3">
            <button
              type="button"
              onClick={() => void sharePaymentLink()}
              className="rounded-lg bg-[#5B2C8E] px-4 py-2 text-sm font-semibold text-white"
            >
              Share payment link
            </button>
            {paymentLink?.url ? (
              <a
                href={paymentLink.short_url || paymentLink.url}
                target="_blank"
                rel="noreferrer"
                className="text-sm font-semibold text-[#5B2C8E] underline"
              >
                Open payment page
              </a>
            ) : null}
          </div>
          {shareMsg ? <p className="mt-2 text-sm text-[#4a3a5c]">{shareMsg}</p> : null}
        </div>
        <div className="rounded-2xl border p-6">
          <h2 className="font-semibold text-[#5B2C8E]">Attendance</h2>
          <pre className="mt-3 overflow-auto text-xs text-[#4a3a5c]">{attendance ? JSON.stringify(attendance, null, 2) : "Loading..."}</pre>
        </div>
        <div className="rounded-2xl border p-6">
          <h2 className="font-semibold text-[#5B2C8E]">Report Cards</h2>
          <p className="mt-2 text-sm text-[#4a3a5c]">{reportCards.length} published report(s)</p>
        </div>
      </section>
    </SiteShell>
  );
}
