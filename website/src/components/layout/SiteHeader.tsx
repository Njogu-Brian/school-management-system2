"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import type { WebsiteSettings } from "@/types/website";
import { BRAND } from "@/content/schoolContent";
import { SocialBar } from "@/components/layout/SocialBar";
import { assetPath } from "@/lib/assetPath";

const NAV = [
  { href: "/", label: "Home" },
  { href: "/about", label: "About Us" },
  { href: "/academics", label: "Academics" },
  { href: "/admissions", label: "Admissions" },
  { href: "/campus-life", label: "Campus & Gallery" },
  { href: "/community", label: "Community" },
  { href: "/contact", label: "Contact" },
  { href: "/parent-portal", label: "Parents" },
];

function logoSrc(settings?: WebsiteSettings) {
  return settings?.logo || BRAND.logoUrl || assetPath("/logo.png");
}

export function SiteHeader({ settings }: { settings?: WebsiteSettings }) {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);
  const schoolName = settings?.school_name || BRAND.shortName;

  return (
    <header className="sticky top-0 z-50 border-b border-white/10 bg-[var(--rk-purple-deep)]/95 backdrop-blur-md">
      <div className="border-b border-white/5 bg-[var(--rk-purple-dark)]/80">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-1.5 text-xs text-white/80 lg:px-8">
          <span className="hidden sm:inline">{CONTACT_LINE}</span>
          <SocialBar compact />
        </div>
      </div>
      <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 lg:px-8">
        <Link href="/" className="flex items-center gap-3">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={logoSrc(settings)}
            alt={`${schoolName} logo`}
            className="h-11 w-11 rounded-full bg-white object-contain p-0.5 shadow-md ring-2 ring-[var(--rk-gold)]"
          />
          <div className="min-w-0">
            <p className="truncate font-serif text-base font-semibold text-white sm:text-lg">{schoolName}</p>
            <p className="truncate text-xs text-[var(--rk-gold)]">{settings?.tagline || BRAND.tagline}</p>
          </div>
        </Link>

        <nav className="hidden items-center gap-0.5 lg:flex xl:gap-1">
          {NAV.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className={`rounded-full px-2.5 py-2 text-sm transition xl:px-3 ${pathname === item.href ? "bg-white/15 text-white" : "text-white/80 hover:bg-white/10 hover:text-white"}`}
            >
              {item.label}
            </Link>
          ))}
          <Link
            href="/admissions"
            className="ml-1 rounded-full bg-[var(--rk-gold)] px-4 py-2 text-sm font-bold text-[var(--rk-purple-deep)] transition hover:brightness-110 xl:ml-2 xl:px-5"
          >
            Apply Now
          </Link>
        </nav>

        <button type="button" className="rounded-lg p-2 text-2xl text-white lg:hidden" onClick={() => setOpen(!open)} aria-label="Menu">
          {open ? "✕" : "☰"}
        </button>
      </div>

      {open && (
        <div className="border-t border-white/10 px-4 py-4 lg:hidden">
          {NAV.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              onClick={() => setOpen(false)}
              className={`block rounded-lg px-2 py-2.5 ${pathname === item.href ? "bg-white/10 font-semibold text-white" : "text-white/90"}`}
            >
              {item.label}
            </Link>
          ))}
          <div className="mt-4 flex justify-center">
            <SocialBar />
          </div>
        </div>
      )}
    </header>
  );
}

const CONTACT_LINE = "📍 Wangige, Kiambu · ☎ +254 719 396 233";
