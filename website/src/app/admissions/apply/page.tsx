"use client";

import { useEffect, useState } from "react";
import { SiteShell } from "@/components/layout/SiteShell";
import { admissionService } from "@/services/admissionService";
import { analyticsService } from "@/services/analyticsService";
import Link from "next/link";

const STEPS = ["Parent Details", "Child Details", "Medical & Needs", "Documents & Submit"];

export default function ApplyPage() {
  const [token, setToken] = useState<string | null>(null);
  const [step, setStep] = useState(1);
  const [form, setForm] = useState<Record<string, string>>({});
  const [appNo, setAppNo] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    admissionService.start().then((app) => {
      setToken(app.draft_token || null);
      analyticsService.trackEvent("application_start", "/admissions/apply");
    });
  }, []);

  async function nextStep() {
    if (!token) return;
    setLoading(true);
    await admissionService.saveStep(token, step, form);
    if (step < 4) setStep(step + 1);
    setLoading(false);
  }

  async function submitAll() {
    if (!token) return;
    setLoading(true);
    const result = await admissionService.submit(token, form as Record<string, string>);
    setAppNo(result.data?.application_no);
    analyticsService.trackEvent("application_complete", "/admissions/apply");
    setLoading(false);
  }

  if (appNo) {
    return (
      <SiteShell>
        <section className="mx-auto max-w-lg px-4 py-20 text-center">
          <h1 className="font-serif text-3xl font-bold text-[#2a1145]">Application Submitted!</h1>
          <p className="mt-4 text-[#4a3a5c]">Your application number is <strong>{appNo}</strong></p>
          <Link href={`/admissions/track?no=${appNo}`} className="mt-6 inline-block text-[#5B2C8E] underline">Track your application</Link>
        </section>
      </SiteShell>
    );
  }

  return (
    <SiteShell>
      <section className="bg-[#5B2C8E] py-12 text-white text-center">
        <h1 className="font-serif text-3xl font-bold">Apply to Royal Kings</h1>
        <p className="mt-2 text-white/80">Step {step} of 4 · {STEPS[step - 1]}</p>
      </section>
      <section className="mx-auto max-w-xl px-4 py-12">
        <div className="mb-8 flex gap-2">{STEPS.map((_, i) => (
          <div key={i} className={`h-2 flex-1 rounded-full ${i < step ? "bg-[#5B2C8E]" : "bg-[#e8dff5]"}`} />
        ))}</div>

        {step === 1 && (<>
          <input className="mb-3 w-full rounded-xl border px-4 py-3" placeholder="Parent full name" value={form.parent_name || ""} onChange={(e) => setForm({ ...form, parent_name: e.target.value })} />
          <input className="mb-3 w-full rounded-xl border px-4 py-3" placeholder="Phone" value={form.phone || ""} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
          <input className="mb-3 w-full rounded-xl border px-4 py-3" placeholder="Email" type="email" value={form.email || ""} onChange={(e) => setForm({ ...form, email: e.target.value })} />
        </>)}

        {step === 2 && (<>
          <input className="mb-3 w-full rounded-xl border px-4 py-3" placeholder="Child full name" value={form.child_name || ""} onChange={(e) => setForm({ ...form, child_name: e.target.value })} />
          <input className="mb-3 w-full rounded-xl border px-4 py-3" placeholder="Date of birth" type="date" value={form.dob || ""} onChange={(e) => setForm({ ...form, dob: e.target.value })} />
          <select className="mb-3 w-full rounded-xl border px-4 py-3" value={form.gender || ""} onChange={(e) => setForm({ ...form, gender: e.target.value })}>
            <option value="">Gender</option><option value="male">Male</option><option value="female">Female</option>
          </select>
          <input className="mb-3 w-full rounded-xl border px-4 py-3" placeholder="Desired class" value={form.desired_class || ""} onChange={(e) => setForm({ ...form, desired_class: e.target.value })} />
          <input className="mb-3 w-full rounded-xl border px-4 py-3" placeholder="Previous school" value={form.previous_school || ""} onChange={(e) => setForm({ ...form, previous_school: e.target.value })} />
        </>)}

        {step === 3 && (<>
          <textarea className="mb-3 w-full rounded-xl border px-4 py-3" rows={3} placeholder="Medical notes" value={form.medical_notes || ""} onChange={(e) => setForm({ ...form, medical_notes: e.target.value })} />
          <textarea className="mb-3 w-full rounded-xl border px-4 py-3" rows={3} placeholder="Special needs" value={form.special_needs || ""} onChange={(e) => setForm({ ...form, special_needs: e.target.value })} />
        </>)}

        {step === 4 && (<>
          <p className="mb-4 text-sm text-[#4a3a5c]">Upload documents (optional — you can also bring them on assessment day).</p>
          {["birth_certificate", "report_form", "passport_photo", "transfer_letter"].map((type) => (
            <label key={type} className="mb-3 block rounded-xl border border-dashed p-4">
              <span className="text-sm font-medium capitalize">{type.replace("_", " ")}</span>
              <input type="file" className="mt-2 block w-full text-sm" onChange={(e) => {
                const file = e.target.files?.[0];
                if (file && token) admissionService.uploadDocument(token, type, file);
              }} />
            </label>
          ))}
        </>)}

        <div className="mt-8 flex gap-3">
          {step > 1 && <button type="button" onClick={() => setStep(step - 1)} className="rounded-full border px-6 py-3">Back</button>}
          {step < 4 ? (
            <button type="button" disabled={loading} onClick={nextStep} className="flex-1 rounded-full bg-[#5B2C8E] py-3 font-semibold text-white">Continue</button>
          ) : (
            <button type="button" disabled={loading} onClick={submitAll} className="flex-1 rounded-full bg-[#D4AF37] py-3 font-semibold text-[#2a1145]">Submit Application</button>
          )}
        </div>
      </section>
    </SiteShell>
  );
}
