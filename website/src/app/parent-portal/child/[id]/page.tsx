"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import { SiteShell } from "@/components/layout/SiteShell";
import { parentService } from "@/services/parentService";

type HomeworkItem = {
  id: number;
  title?: string;
  subject?: string;
  due_date?: string;
  status?: string;
  instructions?: string;
};

export default function ParentChildPage() {
  const { id } = useParams<{ id: string }>();
  const studentId = Number(id);
  const [statement, setStatement] = useState<Record<string, unknown> | null>(null);
  const [attendance, setAttendance] = useState<Record<string, unknown> | null>(null);
  const [reportCards, setReportCards] = useState<unknown[]>([]);
  const [homework, setHomework] = useState<HomeworkItem[]>([]);

  useEffect(() => {
    if (!studentId) return;
    parentService.statement(studentId).then(setStatement).catch(() => setStatement(null));
    parentService.attendance(studentId).then(setAttendance).catch(() => setAttendance(null));
    parentService.reportCards(studentId).then((r) => setReportCards(r.data || [])).catch(() => setReportCards([]));
    parentService.homework(studentId).then((r) => setHomework(r.data || [])).catch(() => setHomework([]));
  }, [studentId]);

  return (
    <SiteShell>
      <section className="mx-auto max-w-4xl px-4 py-12 space-y-8">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <h1 className="font-serif text-3xl font-bold text-[#2a1145]">Learner Overview</h1>
          <Link href={`/parent-portal/payments?student=${studentId}`} className="rounded-full bg-[#5B2C8E] px-5 py-2 text-sm text-white">
            Pay fees
          </Link>
        </div>
        <div className="rounded-2xl border p-6">
          <h2 className="font-semibold text-[#5B2C8E]">Homework</h2>
          <ul className="mt-3 space-y-3">
            {homework.length === 0 && <li className="text-sm text-[#4a3a5c]">No homework assigned.</li>}
            {homework.map((h) => (
              <li key={h.id} className="rounded-xl bg-[#faf6ef] p-3 text-sm">
                <strong>{h.title}</strong>
                {h.subject && <span className="ml-2 text-[#5B2C8E]">{h.subject}</span>}
                {h.due_date && <div className="text-[#4a3a5c]">Due: {h.due_date}</div>}
                <div className="text-xs uppercase text-[#D4AF37]">{h.status}</div>
              </li>
            ))}
          </ul>
        </div>
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
