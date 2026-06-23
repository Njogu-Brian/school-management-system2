"use client";

import { useEffect, useState } from "react";
import { SiteShell } from "@/components/layout/SiteShell";
import { admissionService } from "@/services/admissionService";
import { analyticsService } from "@/services/analyticsService";
import { getErpParentPortalUrl } from "@/lib/erpUrls";
import Link from "next/link";

const STEPS = ["Parent Contact", "Child Details", "Class & Term"];

type ClassroomOption = { id: number; name: string };
type TermOption = { year: number; term: number; label: string };

export default function ApplyPage() {
  const [token, setToken] = useState<string | null>(null);
  const [step, setStep] = useState(1);
  const [form, setForm] = useState<Record<string, string>>({});
  const [appNo, setAppNo] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [classrooms, setClassrooms] = useState<ClassroomOption[]>([]);
  const [terms, setTerms] = useState<TermOption[]>([]);

  useEffect(() => {
    admissionService.start().then((app) => {
      setToken(app.draft_token || null);
      analyticsService.trackEvent("application_start", "/admissions/apply");
    });
    admissionService.options().then((opts) => {
      setClassrooms(opts.classrooms || []);
      setTerms(opts.enrollment_terms || []);
      if (opts.enrollment_terms?.[0]) {
        const first = opts.enrollment_terms[0];
        setForm((f) => ({
          ...f,
          enrollment_year: String(first.year),
          enrollment_term: String(first.term),
        }));
      }
    });
  }, []);

  function canContinue(): boolean {
    if (step === 1) {
      return Boolean(form.parent_name?.trim() && form.phone?.trim() && form.email?.trim());
    }
    if (step === 2) {
      return Boolean(form.child_name?.trim() && form.dob);
    }
    return Boolean(form.preferred_classroom_id && form.enrollment_year && form.enrollment_term);
  }

  async function nextStep() {
    if (!token || !canContinue()) return;
    setError("");
    setLoading(true);
    try {
      await admissionService.saveStep(token, step, form);
      if (step < 3) setStep(step + 1);
    } catch {
      setError("Could not save your progress. Please try again.");
    }
    setLoading(false);
  }

  async function submitAll() {
    if (!token || !canContinue()) return;
    setError("");
    setLoading(true);
    try {
      const result = await admissionService.submit(token, {
        ...form,
        preferred_classroom_id: Number(form.preferred_classroom_id),
        enrollment_year: Number(form.enrollment_year),
        enrollment_term: Number(form.enrollment_term),
      });
      setAppNo(result.data?.application_no);
      analyticsService.trackEvent("application_complete", "/admissions/apply");
    } catch {
      setError("Submission failed. Please check your details and try again.");
    }
    setLoading(false);
  }

  if (appNo) {
    return (
      <SiteShell>
        <section className="mx-auto max-w-lg px-4 py-20 text-center">
          <h1 className="font-serif text-3xl font-bold text-[#2a1145]">Application Submitted!</h1>
          <p className="mt-4 text-[#4a3a5c]">
            Your application number is <strong>{appNo}</strong>
          </p>
          <p className="mt-3 text-sm text-[#4a3a5c]">
            A confirmation has been sent to your email and phone. Our admissions team will contact you shortly.
          </p>
          <Link href={`/admissions/track?no=${appNo}`} className="mt-6 inline-block text-[#5B2C8E] underline">
            Track your application
          </Link>
        </section>
      </SiteShell>
    );
  }

  return (
    <SiteShell>
      <section className="bg-[#5B2C8E] py-12 text-center text-white">
        <h1 className="font-serif text-3xl font-bold">Apply to Royal Kings</h1>
        <p className="mt-2 text-white/80">
          Step {step} of 3 · {STEPS[step - 1]}
        </p>
      </section>
      <section className="mx-auto max-w-xl px-4 py-12">
        <div className="mb-8 flex gap-2">
          {STEPS.map((_, i) => (
            <div key={i} className={`h-2 flex-1 rounded-full ${i < step ? "bg-[#5B2C8E]" : "bg-[#e8dff5]"}`} />
          ))}
        </div>

        {step === 1 && (
          <>
            <input
              className="mb-3 w-full rounded-xl border px-4 py-3"
              placeholder="Parent full name"
              value={form.parent_name || ""}
              onChange={(e) => setForm({ ...form, parent_name: e.target.value })}
            />
            <input
              className="mb-3 w-full rounded-xl border px-4 py-3"
              placeholder="Phone number"
              value={form.phone || ""}
              onChange={(e) => setForm({ ...form, phone: e.target.value })}
            />
            <input
              className="mb-3 w-full rounded-xl border px-4 py-3"
              placeholder="Email address"
              type="email"
              value={form.email || ""}
              onChange={(e) => setForm({ ...form, email: e.target.value })}
            />
          </>
        )}

        {step === 2 && (
          <>
            <input
              className="mb-3 w-full rounded-xl border px-4 py-3"
              placeholder="Child full name"
              value={form.child_name || ""}
              onChange={(e) => setForm({ ...form, child_name: e.target.value })}
            />
            <input
              className="mb-3 w-full rounded-xl border px-4 py-3"
              placeholder="Date of birth"
              type="date"
              value={form.dob || ""}
              onChange={(e) => setForm({ ...form, dob: e.target.value })}
            />
          </>
        )}

        {step === 3 && (
          <>
            <p className="mb-4 text-sm text-[#4a3a5c]">Tell us where you would like your child to join.</p>
            <select
              className="mb-3 w-full rounded-xl border px-4 py-3"
              value={form.preferred_classroom_id || ""}
              onChange={(e) => setForm({ ...form, preferred_classroom_id: e.target.value })}
            >
              <option value="">Desired class</option>
              {classrooms.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>
            <select
              className="mb-3 w-full rounded-xl border px-4 py-3"
              value={
                form.enrollment_year && form.enrollment_term
                  ? `${form.enrollment_year}-${form.enrollment_term}`
                  : ""
              }
              onChange={(e) => {
                const [year, term] = e.target.value.split("-");
                setForm({ ...form, enrollment_year: year, enrollment_term: term });
              }}
            >
              <option value="">Admission year & term</option>
              {terms.map((t) => (
                <option key={`${t.year}-${t.term}`} value={`${t.year}-${t.term}`}>
                  {t.label}
                </option>
              ))}
            </select>
          </>
        )}

        {error && <p className="mb-4 text-sm text-red-600">{error}</p>}

        <div className="mt-8 flex gap-3">
          {step > 1 && (
            <button type="button" onClick={() => setStep(step - 1)} className="rounded-full border px-6 py-3">
              Back
            </button>
          )}
          {step < 3 ? (
            <button
              type="button"
              disabled={loading || !canContinue()}
              onClick={nextStep}
              className="flex-1 rounded-full bg-[#5B2C8E] py-3 font-semibold text-white disabled:opacity-50"
            >
              Continue
            </button>
          ) : (
            <button
              type="button"
              disabled={loading || !canContinue()}
              onClick={submitAll}
              className="flex-1 rounded-full bg-[#D4AF37] py-3 font-semibold text-[#2a1145] disabled:opacity-50"
            >
              Submit Application
            </button>
          )}
        </div>
      </section>
    </SiteShell>
  );
}
