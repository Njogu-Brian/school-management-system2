"use client";

import { SiteShell } from "@/components/layout/SiteShell";
import { getErpParentPortalUrl } from "@/lib/erpUrls";

export default function ParentPortalPage() {
  const portalUrl = getErpParentPortalUrl();

  return (
    <SiteShell>
      <section className="mx-auto max-w-md px-4 py-16">
        <h1 className="text-center font-serif text-3xl font-bold text-[#2a1145]">Parent Portal</h1>
        <p className="mt-2 text-center text-[#4a3a5c]">
          Sign in to view fees, attendance, report cards, and school announcements for your children.
        </p>
        <div className="mt-8 space-y-4 rounded-2xl border border-[#e8dff5] bg-white p-6 shadow">
          <a
            href={portalUrl}
            className="block w-full rounded-full bg-[#5B2C8E] py-3 text-center font-semibold text-white transition hover:brightness-110"
          >
            Sign In to Parent Portal
          </a>
          <p className="text-center text-xs text-[#4a3a5c]">
            You will be taken to the secure Royal Kings parent dashboard. Use the email and password provided at enrolment.
          </p>
        </div>
      </section>
    </SiteShell>
  );
}
