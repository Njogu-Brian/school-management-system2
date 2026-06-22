import Link from "next/link";
import type { WebsiteSettings } from "@/types/website";
import { BRAND, CONTACT, SOCIAL } from "@/content/schoolContent";
import { SocialBar } from "@/components/layout/SocialBar";
import { assetPath } from "@/lib/assetPath";

export function SiteFooter({ settings }: { settings?: WebsiteSettings }) {
  const logo = settings?.logo || BRAND.logoUrl || assetPath("/logo.png");

  return (
    <footer className="bg-[var(--rk-purple-deep)] text-white">
      <div className="border-b border-white/10 bg-[var(--rk-purple-dark)]/50">
        <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-8 sm:flex-row lg:px-8">
          <div>
            <p className="font-serif text-lg font-bold text-[var(--rk-gold)]">Stay Connected</p>
            <p className="mt-1 text-sm text-white/70">Admissions updates, events, and school news.</p>
          </div>
          <form className="flex w-full max-w-md gap-2" action="/contact" method="get">
            <input
              type="email"
              name="subject"
              placeholder="Your email address"
              className="flex-1 rounded-full border border-white/20 bg-white/10 px-4 py-2.5 text-sm text-white placeholder:text-white/50 focus:border-[var(--rk-gold)] focus:outline-none"
              aria-label="Email for newsletter"
            />
            <button type="submit" className="rounded-full bg-[var(--rk-gold)] px-5 py-2.5 text-sm font-bold text-[var(--rk-purple-deep)] hover:brightness-110">
              Subscribe
            </button>
          </form>
        </div>
      </div>

      <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-12 lg:px-8">
        <div className="lg:col-span-4">
          <div className="flex items-center gap-3">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={logo} alt="" className="h-14 w-14 rounded-full bg-white object-contain p-0.5 ring-2 ring-[var(--rk-gold)]" />
            <div>
              <h3 className="font-serif text-lg text-[var(--rk-gold)]">{settings?.school_name || BRAND.shortName}</h3>
              <p className="text-xs text-white/60">Since {BRAND.founded}</p>
            </div>
          </div>
          <p className="mt-4 text-sm leading-relaxed text-white/70">{settings?.tagline || BRAND.tagline}</p>
          <div className="mt-5">
            <SocialBar />
          </div>
        </div>

        <div className="lg:col-span-2">
          <h4 className="text-xs font-bold uppercase tracking-wider text-[var(--rk-gold)]">Discover</h4>
          <div className="mt-4 flex flex-col gap-2 text-sm text-white/80">
            <Link href="/about">About Us</Link>
            <Link href="/academics">Academics</Link>
            <Link href="/leadership">Leadership</Link>
            <Link href="/calendar">Calendar</Link>
          </div>
        </div>

        <div className="lg:col-span-2">
          <h4 className="text-xs font-bold uppercase tracking-wider text-[var(--rk-gold)]">Admissions</h4>
          <div className="mt-4 flex flex-col gap-2 text-sm text-white/80">
            <Link href="/admissions">How to Apply</Link>
            <Link href="/fees">School Fees</Link>
            <Link href="/transport">Transport</Link>
            <Link href="/child-safety">Child Safety</Link>
          </div>
        </div>

        <div className="lg:col-span-2">
          <h4 className="text-xs font-bold uppercase tracking-wider text-[var(--rk-gold)]">Campus Life</h4>
          <div className="mt-4 flex flex-col gap-2 text-sm text-white/80">
            <Link href="/campus-life">Gallery</Link>
            <Link href="/community">Community</Link>
            <Link href="/blog">News</Link>
            <Link href="/parent-portal">Parent Portal</Link>
          </div>
        </div>

        <div className="lg:col-span-2">
          <h4 className="text-xs font-bold uppercase tracking-wider text-[var(--rk-gold)]">Visit Us</h4>
          <p className="mt-4 text-sm text-white/80">{settings?.address || CONTACT.address}</p>
          <p className="text-sm text-white/80">{settings?.phone || CONTACT.phone}</p>
          <a href={CONTACT.mapsUrl} target="_blank" rel="noreferrer" className="mt-3 inline-block text-sm text-[var(--rk-gold)] hover:underline">
            Open in Google Maps →
          </a>
        </div>
      </div>

      <div className="border-t border-white/10">
        <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-4 text-xs text-white/50 sm:flex-row lg:px-8">
          <p>© {new Date().getFullYear()} {BRAND.name}. All rights reserved.</p>
          <div className="flex gap-4">
            <a href={SOCIAL.facebook} target="_blank" rel="noreferrer">Facebook</a>
            <a href={SOCIAL.instagram} target="_blank" rel="noreferrer">Instagram</a>
            <a href={`https://wa.me/${CONTACT.whatsapp}`} target="_blank" rel="noreferrer">WhatsApp</a>
          </div>
        </div>
      </div>
    </footer>
  );
}
