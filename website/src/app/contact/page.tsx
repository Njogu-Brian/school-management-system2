"use client";

import { SiteShell } from "@/components/layout/SiteShell";
import { useWebsiteSettings } from "@/hooks/useWebsiteData";

export default function ContactPage() {
  const { data: settings } = useWebsiteSettings();

  return (
    <SiteShell>
      <section className="bg-[#5B2C8E] py-16 text-white"><div className="mx-auto max-w-4xl px-4 text-center"><h1 className="font-serif text-4xl font-bold">Contact Us</h1></div></section>
      <section className="mx-auto max-w-4xl px-4 py-16 lg:px-8">
        <div className="grid gap-8 md:grid-cols-2">
          <div className="rounded-2xl bg-[#faf7ff] p-8">
            <h2 className="font-serif text-xl font-bold text-[#2a1145]">Get in Touch</h2>
            <p className="mt-4 text-[#4a3a5c]">{settings?.address}</p>
            <p className="mt-2 text-[#4a3a5c]">{settings?.phone}</p>
            <p className="text-[#4a3a5c]">{settings?.email}</p>
          </div>
          {settings?.google_map && (
            <div className="overflow-hidden rounded-2xl" dangerouslySetInnerHTML={{ __html: settings.google_map }} />
          )}
        </div>
      </section>
    </SiteShell>
  );
}
