import Link from "next/link";
import type { WebsiteSettings } from "@/types/website";
import { BRAND, CONTACT, SOCIAL } from "@/content/schoolContent";
import { SocialBar } from "@/components/layout/SocialBar";
import { assetPath } from "@/lib/assetPath";
import { getErpParentPortalUrl } from "@/lib/erpUrls";

const QUICK_LINKS: Array<{ href: string; label: string; external?: boolean }> = [
  { href: "/admissions/apply", label: "Apply Now" },
  { href: "/fees", label: "School Fees" },
  { href: "/academics", label: "Academics" },
  { href: "/campus-life", label: "Gallery" },
  { href: "/leadership", label: "Leadership" },
  { href: "/child-safety", label: "Child Safety" },
  { href: getErpParentPortalUrl(), label: "Parent Portal", external: true },
];

export function SiteFooter({ settings }: { settings?: WebsiteSettings }) {
  const logo = settings?.logo || BRAND.logoUrl || assetPath("/logo.png");
  const phone = settings?.phone || CONTACT.phone;
  const email = settings?.email || CONTACT.email;
  const admissionsEmail = CONTACT.admissionsEmail;
  const address = settings?.address || CONTACT.address;

  return (
    <footer className="bg-rk-deep-purple text-white">
      {/* Newsletter */}
      <div className="border-b border-white/10">
        <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-rk-5 px-4 py-rk-8 sm:flex-row lg:px-8">
          <div>
            <p className="font-serif text-lg font-bold text-rk-gold">Stay Connected</p>
            <p className="mt-rk-1 text-sm text-white/70">Admissions updates, events, and school news.</p>
          </div>
          <form className="flex w-full max-w-md gap-rk-2" action="/contact" method="get">
            <input
              type="email"
              name="subject"
              placeholder="Your email address"
              className="flex-1 rounded-rk-pill border border-white/20 bg-white/10 px-rk-4 py-2.5 text-sm text-white placeholder:text-white/50 focus:border-rk-gold focus:outline-none"
              aria-label="Email for newsletter"
            />
            <button type="submit" className="rk-btn rk-btn-primary shrink-0 cursor-pointer">
              Subscribe
            </button>
          </form>
        </div>
      </div>

      <div className="mx-auto grid max-w-7xl gap-rk-10 px-4 py-rk-12 lg:grid-cols-12 lg:gap-rk-8 lg:px-8">
        {/* Brand + contact */}
        <div className="lg:col-span-4">
          <div className="flex items-center gap-rk-3">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={logo} alt="" className="h-14 w-14 rounded-full bg-white object-contain p-0.5 ring-2 ring-rk-gold" />
            <div>
              <h3 className="font-serif text-lg text-rk-gold">{settings?.school_name || BRAND.shortName}</h3>
              <p className="text-xs text-white/60">Since {BRAND.founded}</p>
            </div>
          </div>
          <p className="mt-rk-4 text-sm leading-relaxed text-white/70">{settings?.tagline || BRAND.tagline}</p>

          <ul className="mt-rk-6 space-y-rk-2 text-sm text-white/80">
            <li>
              <a href={`tel:${CONTACT.phoneRaw}`} className="hover:text-rk-gold">{phone}</a>
            </li>
            <li>
              <a href={`https://wa.me/${CONTACT.whatsapp}`} target="_blank" rel="noreferrer" className="hover:text-rk-gold">
                WhatsApp: {phone}
              </a>
            </li>
            <li>
              <a href={`mailto:${email}`} className="hover:text-rk-gold">{email}</a>
            </li>
            <li>
              <a href={`mailto:${admissionsEmail}`} className="hover:text-rk-gold">Admissions: {admissionsEmail}</a>
            </li>
            <li className="pt-rk-1 text-white/70">{address}</li>
            <li className="text-white/60">{CONTACT.postal}</li>
          </ul>

          <div className="mt-rk-5">
            <SocialBar />
          </div>
        </div>

        {/* Quick links */}
        <div className="lg:col-span-2">
          <h4 className="text-xs font-bold uppercase tracking-wider text-rk-gold">Quick Links</h4>
          <nav className="mt-rk-4 flex flex-col gap-rk-2 text-sm text-white/80">
            {QUICK_LINKS.map((link) =>
              link.external ? (
                <a key={link.href} href={link.href} className="hover:text-white">
                  {link.label}
                </a>
              ) : (
                <Link key={link.href} href={link.href} className="hover:text-white">
                  {link.label}
                </Link>
              )
            )}
          </nav>
        </div>

        {/* Map preview */}
        <div className="lg:col-span-6">
          <h4 className="text-xs font-bold uppercase tracking-wider text-rk-gold">Visit Us</h4>
          <p className="mt-rk-3 text-sm text-white/80">{address}</p>
          <div className="mt-rk-4 overflow-hidden rounded-rk-xl border border-white/10 shadow-rk-lg">
            <iframe
              title="Royal Kings Premier School location"
              src={CONTACT.mapsEmbed}
              className="h-48 w-full border-0 sm:h-56"
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
              allowFullScreen
            />
          </div>
          <a
            href={CONTACT.mapsUrl}
            target="_blank"
            rel="noreferrer"
            className="mt-rk-3 inline-block text-sm font-semibold text-rk-gold hover:underline"
          >
            Open in Google Maps →
          </a>
        </div>
      </div>

      <div className="border-t border-white/10">
        <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-rk-3 px-4 py-rk-4 text-xs text-white/50 sm:flex-row lg:px-8">
          <p>© {new Date().getFullYear()} {BRAND.name}. All rights reserved.</p>
          <div className="flex flex-wrap justify-center gap-rk-4">
            <a href={SOCIAL.facebook} target="_blank" rel="noreferrer" className="hover:text-white">Facebook</a>
            <a href={SOCIAL.instagram} target="_blank" rel="noreferrer" className="hover:text-white">Instagram</a>
            <a href={SOCIAL.tiktok} target="_blank" rel="noreferrer" className="hover:text-white">TikTok</a>
            <a href={`https://wa.me/${CONTACT.whatsapp}`} target="_blank" rel="noreferrer" className="hover:text-white">WhatsApp</a>
          </div>
        </div>
      </div>
    </footer>
  );
}
