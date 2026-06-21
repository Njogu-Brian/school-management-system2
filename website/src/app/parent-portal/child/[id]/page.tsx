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

  useEffect(() => {
    if (!studentId) return;
    parentService.statement(studentId).then(setStatement).catch(() => setStatement(null));
    parentService.attendance(studentId).then(setAttendance).catch(() => setAttendance(null));
    parentService.reportCards(studentId).then((r) => setReportCards(r.data || [])).catch(() => setReportCards([]));
  }, [studentId]);

  return (
    <SiteShell>
      <section className="mx-auto max-w-4xl px-4 py-12 space-y-8">
        <h1 className="font-serif text-3xl font-bold text-[#2a1145]">Learner Overview</h1>
        <div className="rounded-2xl border p-6">
          <h2 className="font-semibold text-[#5B2C8E]">Fee Statement</h2>
          <pre className="mt-3 overflow-auto text-xs text-[#4a3a5c]">{statement ? JSON.stringify(statement, null, 2) : "Loading..."}</pre>
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
