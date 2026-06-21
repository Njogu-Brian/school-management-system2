import Link from "next/link";
import type { WebsiteSettings } from "@/types/website";
import { BRAND, CONTACT } from "@/content/schoolContent";

export function SiteFooter({ settings }: { settings?: WebsiteSettings }) {
  return (
    <footer className="bg-[var(--rk-purple-deep)] text-white">
      <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-2 md:grid-cols-4 lg:px-8">
        <div className="sm:col-span-2 md:col-span-1">
          <h3 className="font-serif text-xl text-[var(--rk-gold)]">{settings?.school_name || BRAND.name}</h3>
          <p className="mt-3 text-sm text-white/70">{settings?.tagline || BRAND.tagline}</p>
          <p className="mt-4 text-sm text-white/70">{settings?.address || CONTACT.address}</p>
          <p className="text-sm text-white/70">{CONTACT.postal}</p>
        </div>
        <div>
          <h4 className="font-semibold text-[var(--rk-gold)]">Quick Links</h4>
          <div className="mt-3 flex flex-col gap-2 text-sm text-white/80">
            <Link href="/about">About Us</Link>
            <Link href="/admissions">Admissions & Fees</Link>
            <Link href="/academics">Academics</Link>
            <Link href="/gallery">Gallery</Link>
            <Link href="/contact">Contact</Link>
            <Link href="/parent-portal">Parent Portal</Link>
          </div>
        </div>
        <div>
          <h4 className="font-semibold text-[var(--rk-gold)]">Contact</h4>
          <p className="mt-3 text-sm text-white/80">{settings?.phone || CONTACT.phone}</p>
          <p className="text-sm text-white/80">{settings?.email || CONTACT.email}</p>
          <p className="mt-2 text-xs text-white/60">{CONTACT.hours.weekdays}</p>
          <p className="text-xs text-white/60">{CONTACT.hours.saturday}</p>
        </div>
        <div>
          <h4 className="font-semibold text-[var(--rk-gold)]">Find Us</h4>
          <a href={CONTACT.mapsUrl} target="_blank" rel="noreferrer" className="mt-3 inline-block text-sm text-white/80 hover:text-[var(--rk-gold)]">
            Open in Google Maps →
          </a>
          <a href={`https://wa.me/${CONTACT.whatsapp}`} target="_blank" rel="noreferrer" className="mt-3 block text-sm text-white/80 hover:text-[#25D366]">
            WhatsApp Us →
          </a>
        </div>
      </div>
      <div className="border-t border-white/10 py-4 text-center text-xs text-white/50">
        © {new Date().getFullYear()} {BRAND.name}. Serving families since {BRAND.founded}.
      </div>
    </footer>
  );
}
