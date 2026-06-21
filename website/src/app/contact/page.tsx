"use client";

import { RichPage, PageHero, SectionBlock } from "@/components/layout/RichPage";
import { CONTACT, FEES_NOTE } from "@/content/schoolContent";
import { useWebsiteSettings } from "@/hooks/useWebsiteData";
import Link from "next/link";

export default function ContactPage() {
  const { data: settings } = useWebsiteSettings();
  const phone = settings?.phone || CONTACT.phone;
  const email = settings?.email || CONTACT.email;
  const address = settings?.address || CONTACT.address;

  return (
    <RichPage>
      <PageHero title="Contact Us" subtitle="We would love to hear from you — book a tour, ask about admissions, or visit our Wangige campus." />
      <SectionBlock>
        <div className="grid gap-6 lg:grid-cols-2 lg:gap-8">
          <div className="space-y-6">
            <div className="rounded-2xl bg-[var(--rk-surface)] p-6 sm:p-8">
              <h2 className="font-serif text-xl font-bold text-[var(--rk-purple-dark)]">Get in Touch</h2>
              <ul className="mt-4 space-y-3 text-sm text-[var(--rk-muted)] sm:text-base">
                <li><strong className="text-[var(--rk-purple)]">Phone:</strong> <a href={`tel:${phone}`} className="hover:underline">{phone}</a></li>
                <li><strong className="text-[var(--rk-purple)]">WhatsApp:</strong> <a href={`https://wa.me/${CONTACT.whatsapp}`} className="hover:underline" target="_blank" rel="noreferrer">+{CONTACT.whatsapp}</a></li>
                <li><strong className="text-[var(--rk-purple)]">Email:</strong> <a href={`mailto:${email}`} className="hover:underline">{email}</a></li>
                <li><strong className="text-[var(--rk-purple)]">Address:</strong> {address}</li>
                <li><strong className="text-[var(--rk-purple)]">Postal:</strong> {CONTACT.postal}</li>
                <li><strong className="text-[var(--rk-purple)]">Hours:</strong> {CONTACT.hours.weekdays}<br />{CONTACT.hours.saturday}</li>
              </ul>
              <div className="mt-6 flex flex-wrap gap-3">
                <a href={`https://wa.me/${CONTACT.whatsapp}`} target="_blank" rel="noreferrer" className="rounded-full bg-[#25D366] px-5 py-2 text-sm font-semibold text-white">
                  WhatsApp Us
                </a>
                <a href={CONTACT.mapsUrl} target="_blank" rel="noreferrer" className="rounded-full bg-[var(--rk-purple)] px-5 py-2 text-sm font-semibold text-white">
                  Open in Maps
                </a>
              </div>
            </div>
            <div className="rounded-2xl border border-[var(--rk-border)] p-6 sm:p-8">
              <h2 className="font-serif text-xl font-bold text-[var(--rk-purple-dark)]">{FEES_NOTE.title}</h2>
              <p className="prose-rk mt-3 text-sm sm:text-base">{FEES_NOTE.intro}</p>
              <ul className="mt-4 list-disc space-y-2 pl-5 text-sm text-[var(--rk-muted)]">
                {FEES_NOTE.points.map((p) => (
                  <li key={p}>{p}</li>
                ))}
              </ul>
              <Link href="/admissions" className="mt-4 inline-block text-sm font-semibold text-[var(--rk-purple)] hover:underline">
                {FEES_NOTE.cta} →
              </Link>
            </div>
          </div>
          <div className="overflow-hidden rounded-2xl shadow-lg ring-1 ring-[var(--rk-border)]">
            <iframe
              title="Royal Kings School on Google Maps"
              src={CONTACT.mapsEmbed}
              className="h-[280px] w-full sm:h-[360px] lg:h-full lg:min-h-[420px]"
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
              allowFullScreen
            />
          </div>
        </div>
      </SectionBlock>
    </RichPage>
  );
}
