"use client";

import { RichPage, PageHero, SectionBlock, CtaBanner } from "@/components/layout/RichPage";
import { ADMISSIONS_CONTENT, FEES_NOTE } from "@/content/schoolContent";
import { LEGACY_HEROES } from "@/content/legacyGallery";
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
      <PageHero
        title="Admissions"
        subtitle={ADMISSIONS_CONTENT.welcome}
        image={LEGACY_HEROES.admissions}
      />
      <SectionBlock>
        <div className="grid gap-8 lg:grid-cols-2 lg:gap-12">
          <div>
            <h2 className="font-serif text-2xl font-bold text-[var(--rk-purple-dark)]">Your Journey With Us</h2>
            <p className="mt-3 text-sm text-[var(--rk-muted)]">{ADMISSIONS_CONTENT.journeyNote}</p>
            <ol className="mt-4 space-y-3 text-sm text-[var(--rk-muted)] sm:text-base">
              {ADMISSIONS_CONTENT.procedure.map((step, i) => (
                <li key={step}>
                  <strong>{i + 1}.</strong> {step}
                </li>
              ))}
            </ol>
            <Link
              href="/admissions/apply"
              className="mt-6 inline-block rounded-full bg-[var(--rk-purple)] px-6 py-3 text-sm font-semibold text-white hover:brightness-110"
            >
              Start Online Application
            </Link>
            <Link href="/admissions/faq" className="ml-3 inline-block text-sm text-[var(--rk-purple)] underline">
              Admissions FAQ
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

      <SectionBlock title="Application Requirements" alt>
        <div className="grid gap-8 md:grid-cols-2">
          <article className="rounded-2xl bg-white p-6 ring-1 ring-[var(--rk-border)]">
            <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">{ADMISSIONS_CONTENT.newStudent.title}</h3>
            <ul className="mt-4 list-disc space-y-2 pl-5 text-sm text-[var(--rk-muted)]">
              {ADMISSIONS_CONTENT.newStudent.items.map((item) => (
                <li key={item}>{item}</li>
              ))}
            </ul>
          </article>
          <article className="rounded-2xl bg-white p-6 ring-1 ring-[var(--rk-border)]">
            <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">{ADMISSIONS_CONTENT.transfer.title}</h3>
            <ul className="mt-4 list-disc space-y-2 pl-5 text-sm text-[var(--rk-muted)]">
              {ADMISSIONS_CONTENT.transfer.items.map((item) => (
                <li key={item}>{item}</li>
              ))}
            </ul>
          </article>
        </div>
        <div className="mt-8 flex justify-center">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={LEGACY_HEROES.admissionsPromo} alt="Admissions open" className="max-w-xs rounded-2xl shadow-lg" />
        </div>
      </SectionBlock>

      <SectionBlock title={FEES_NOTE.title}>
        <p className="mx-auto max-w-2xl text-center text-[var(--rk-muted)]">{FEES_NOTE.intro}</p>
        <ul className="mx-auto mt-6 grid max-w-3xl gap-3 sm:grid-cols-2">
          {FEES_NOTE.points.map((p) => (
            <li key={p} className="rounded-xl bg-white p-4 text-sm text-[var(--rk-muted)] ring-1 ring-[var(--rk-border)]">{p}</li>
          ))}
        </ul>
      </SectionBlock>

      <SectionBlock alt>
        <CtaBanner title="2025 Admissions Open" body="Limited spaces available. Begin your child's Royal Kings journey today." href="/admissions/apply" label="Apply Now" />
      </SectionBlock>
    </RichPage>
  );
}
