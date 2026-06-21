"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import type { WebsiteSettings } from "@/types/website";
import { BRAND } from "@/content/schoolContent";

const NAV = [
  { href: "/", label: "Home" },
  { href: "/about", label: "About" },
  { href: "/academics", label: "Academics" },
  { href: "/admissions", label: "Admissions" },
  { href: "/campus-life", label: "Campus Life" },
  { href: "/co-curricular", label: "Co-curricular" },
  { href: "/gallery", label: "Gallery" },
  { href: "/events", label: "Events" },
  { href: "/spotlights", label: "Spotlights" },
  { href: "/community", label: "Community" },
  { href: "/contact", label: "Contact" },
  { href: "/parent-portal", label: "Parent Portal" },
];

export function SiteHeader({ settings }: { settings?: WebsiteSettings }) {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);
  const primary = settings?.primary_color || BRAND.purple;

  return (
    <header className="sticky top-0 z-50 border-b border-white/10 bg-[var(--rk-purple-deep)]/95 backdrop-blur-md">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 lg:px-8">
        <Link href="/" className="flex items-center gap-3">
          {settings?.logo ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={settings.logo} alt={settings.school_name} className="h-10 w-auto" />
          ) : (
            <span className="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--rk-gold)] text-sm font-bold text-[var(--rk-purple-deep)]">RK</span>
          )}
          <div>
            <p className="font-serif text-lg font-semibold text-white">{settings?.school_name || "Royal Kings"}</p>
            <p className="text-xs text-[var(--rk-gold)]">{settings?.tagline || BRAND.tagline}</p>
          </div>
        </Link>

        <nav className="hidden items-center gap-1 xl:flex">
          {NAV.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className={`rounded-full px-3 py-2 text-sm transition ${pathname === item.href ? "bg-white/15 text-white" : "text-white/80 hover:bg-white/10 hover:text-white"}`}
            >
              {item.label}
            </Link>
          ))}
          <Link
            href="/admissions"
            className="ml-2 rounded-full px-5 py-2 text-sm font-semibold text-[var(--rk-purple-deep)] transition hover:brightness-110"
            style={{ backgroundColor: settings?.secondary_color || BRAND.gold }}
          >
            Apply Now
          </Link>
        </nav>

        <button type="button" className="xl:hidden text-white" onClick={() => setOpen(!open)} aria-label="Menu">
          ☰
        </button>
      </div>

      {open && (
        <div className="border-t border-white/10 px-4 py-4 xl:hidden">
          {NAV.map((item) => (
            <Link key={item.href} href={item.href} onClick={() => setOpen(false)} className="block py-2 text-white/90" style={{ color: pathname === item.href ? primary : undefined }}>
              {item.label}
            </Link>
          ))}
        </div>
      )}
    </header>
  );
}
