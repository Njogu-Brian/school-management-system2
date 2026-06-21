"use client";

import { SiteShell } from "@/components/layout/SiteShell";
import { websiteService } from "@/services/websiteService";
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
    <SiteShell>
      <section className="bg-[#5B2C8E] py-16 text-white">
        <div className="mx-auto max-w-3xl px-4 text-center">
          <h1 className="font-serif text-4xl font-bold">Admissions</h1>
          <p className="mt-4 text-white/85">Begin your child&apos;s journey from age 3 to Grade 9.</p>
        </div>
      </section>
      <section className="mx-auto max-w-xl px-4 py-16">
        <form onSubmit={handleSubmit} className="space-y-4 rounded-3xl border border-[#e8dff5] bg-white p-8 shadow-lg">
          <input name="parent_name" placeholder="Parent name" required className="w-full rounded-xl border px-4 py-3" />
          <input name="phone" placeholder="Phone" required className="w-full rounded-xl border px-4 py-3" />
          <input name="email" type="email" placeholder="Email" required className="w-full rounded-xl border px-4 py-3" />
          <input name="child_age" placeholder="Child age" className="w-full rounded-xl border px-4 py-3" />
          <input name="grade_interest" placeholder="Grade interest" className="w-full rounded-xl border px-4 py-3" />
          <textarea name="message" placeholder="Message" rows={4} className="w-full rounded-xl border px-4 py-3" />
          <button type="submit" disabled={status === "loading"} className="w-full rounded-full bg-[#5B2C8E] py-3 font-semibold text-white">
            {status === "loading" ? "Submitting..." : "Submit Enquiry"}
          </button>
          {status === "success" && <p className="text-center text-green-600">Thank you! We will contact you shortly.</p>}
          {status === "error" && <p className="text-center text-red-600">Something went wrong. Please try again.</p>}
        </form>
      </section>
    </SiteShell>
  );
}
