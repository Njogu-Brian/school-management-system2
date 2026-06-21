import Link from "next/link";
import type { WebsiteSettings } from "@/types/website";
import { BRAND, CONTACT, SOCIAL } from "@/content/schoolContent";
import { SocialBar } from "@/components/layout/SocialBar";
import { assetPath } from "@/lib/assetPath";

export function SiteFooter({ settings }: { settings?: WebsiteSettings }) {
  const logo = settings?.logo || BRAND.logoUrl || assetPath("/logo.png");

  return (
    <footer className="bg-[var(--rk-purple-deep)] text-white">
      <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-2 md:grid-cols-4 lg:px-8">
        <div className="sm:col-span-2 md:col-span-1">
          <div className="flex items-center gap-3">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={logo} alt="" className="h-12 w-12 rounded-full bg-white object-contain p-0.5 ring-2 ring-[var(--rk-gold)]" />
            <h3 className="font-serif text-lg text-[var(--rk-gold)]">{settings?.school_name || BRAND.shortName}</h3>
          </div>
          <p className="mt-3 text-sm text-white/70">{settings?.tagline || BRAND.tagline}</p>
          <p className="mt-4 text-sm text-white/70">{settings?.address || CONTACT.address}</p>
          <p className="text-sm text-white/70">{CONTACT.postal}</p>
          <div className="mt-5">
            <SocialBar />
          </div>
        </div>
        <div>
          <h4 className="font-semibold text-[var(--rk-gold)]">Quick Links</h4>
          <div className="mt-3 flex flex-col gap-2 text-sm text-white/80">
            <Link href="/about">About Us</Link>
            <Link href="/admissions">Admissions & Fees</Link>
            <Link href="/academics">Academics</Link>
            <Link href="/campus-life">Campus & Gallery</Link>
            <Link href="/community">Community</Link>
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
          <h4 className="font-semibold text-[var(--rk-gold)]">Follow Us</h4>
          <div className="mt-3 flex flex-col gap-2 text-sm text-white/80">
            <a href={SOCIAL.facebook} target="_blank" rel="noreferrer">Facebook</a>
            <a href={SOCIAL.instagram} target="_blank" rel="noreferrer">Instagram</a>
            <a href={SOCIAL.tiktok} target="_blank" rel="noreferrer">TikTok</a>
            <a href={CONTACT.mapsUrl} target="_blank" rel="noreferrer">Google Maps</a>
            <a href={`https://wa.me/${CONTACT.whatsapp}`} target="_blank" rel="noreferrer">WhatsApp</a>
          </div>
        </div>
      </div>
      <div className="border-t border-white/10 py-4 text-center text-xs text-white/50">
        © {new Date().getFullYear()} {BRAND.name}. Serving families since {BRAND.founded}.
      </div>
    </footer>
  );
}
