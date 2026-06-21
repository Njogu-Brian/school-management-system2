"use client";

import { RichPage, PageHero, SectionBlock } from "@/components/layout/RichPage";
import { FEES_NOTE, LEGACY_IMAGES } from "@/content/schoolContent";
import { websiteService } from "@/services/websiteService";
import Link from "next/link";
import { useState } from "react";

export default function AdmissionsPage() {
  const [status, setStatus] = useState<"idle" | "loading" | "success" | "error">("idle");

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setStatus("loading");
    const form = new FormData(e.currentTarget);
    try {
      await websiteService.submitEnquiry(Object.fromEntries(form.entries()) as Record<string, string>);
      setStatus("success");
      e.currentTarget.reset();
    } catch {
      setStatus("error");
    }
  }

  return (
    <RichPage>
      <PageHero title="Admissions" subtitle="Begin your child's journey — Creche to Grade 9. 2025 admissions are open." image={LEGACY_IMAGES.admissions} />
      <SectionBlock>
        <div className="grid gap-8 lg:grid-cols-2 lg:gap-12">
          <div>
            <h2 className="font-serif text-2xl font-bold text-[var(--rk-purple-dark)]">How to Apply</h2>
            <ol className="mt-4 space-y-3 text-sm text-[var(--rk-muted)] sm:text-base">
              <li><strong>1.</strong> Submit an enquiry or book a school tour</li>
              <li><strong>2.</strong> Complete the online application form</li>
              <li><strong>3.</strong> Upload required documents</li>
              <li><strong>4.</strong> Assessment & family interview</li>
              <li><strong>5.</strong> Receive admission decision</li>
            </ol>
            <Link href="/admissions/apply" className="mt-6 inline-block rounded-full bg-[var(--rk-purple)] px-6 py-3 text-sm font-semibold text-white hover:brightness-110">
              Start Online Application
            </Link>
          </div>
          <form onSubmit={handleSubmit} className="space-y-4 rounded-3xl border border-[var(--rk-border)] bg-white p-6 shadow-lg sm:p-8">
            <h3 className="font-serif text-xl font-bold text-[var(--rk-purple-dark)]">Quick Enquiry</h3>
            <input name="parent_name" placeholder="Parent name" required className="w-full rounded-xl border px-4 py-3 text-sm" />
            <input name="phone" placeholder="Phone" required className="w-full rounded-xl border px-4 py-3 text-sm" />
            <input name="email" type="email" placeholder="Email" required className="w-full rounded-xl border px-4 py-3 text-sm" />
            <input name="child_age" placeholder="Child age" className="w-full rounded-xl border px-4 py-3 text-sm" />
            <input name="grade_interest" placeholder="Grade interest" className="w-full rounded-xl border px-4 py-3 text-sm" />
            <textarea name="message" placeholder="Message" rows={4} className="w-full rounded-xl border px-4 py-3 text-sm" />
            <button type="submit" disabled={status === "loading"} className="w-full rounded-full bg-[var(--rk-purple)] py-3 font-semibold text-white">
              {status === "loading" ? "Submitting..." : "Submit Enquiry"}
            </button>
            {status === "success" && <p className="text-center text-sm text-green-600">Thank you! We will contact you shortly.</p>}
            {status === "error" && <p className="text-center text-sm text-red-600">Something went wrong. Please try again or WhatsApp us.</p>}
          </form>
        </div>
      </SectionBlock>
      <SectionBlock title={FEES_NOTE.title} alt>
        <p className="mx-auto max-w-2xl text-center text-[var(--rk-muted)]">{FEES_NOTE.intro}</p>
        <ul className="mx-auto mt-6 grid max-w-3xl gap-3 sm:grid-cols-2">
          {FEES_NOTE.points.map((p) => (
            <li key={p} className="rounded-xl bg-white p-4 text-sm text-[var(--rk-muted)] ring-1 ring-[var(--rk-border)]">{p}</li>
          ))}
        </ul>
      </SectionBlock>
    </RichPage>
  );
}
