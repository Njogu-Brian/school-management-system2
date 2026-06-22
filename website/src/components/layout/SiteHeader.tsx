"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import type { WebsiteSettings } from "@/types/website";
import { BRAND } from "@/content/schoolContent";
import { SocialBar } from "@/components/layout/SocialBar";
import { assetPath } from "@/lib/assetPath";

const NAV_GROUPS = [
  {
    label: "Discover",
    items: [
      { href: "/about", label: "About Us" },
      { href: "/academics", label: "Academics" },
      { href: "/leadership", label: "Leadership" },
      { href: "/calendar", label: "Calendar" },
    ],
  },
  {
    label: "Admissions",
    items: [
      { href: "/admissions", label: "How to Apply" },
      { href: "/fees", label: "School Fees" },
      { href: "/transport", label: "Transport" },
      { href: "/child-safety", label: "Child Safety" },
    ],
  },
  {
    label: "Life at Royal Kings",
    items: [
      { href: "/campus-life", label: "Campus & Gallery" },
      { href: "/community", label: "Community" },
      { href: "/blog", label: "News & Stories" },
    ],
  },
];

const PRIMARY = [
  { href: "/", label: "Home" },
  { href: "/contact", label: "Contact" },
  { href: "/parent-portal", label: "Parents" },
];

function logoSrc(settings?: WebsiteSettings) {
  return settings?.logo || BRAND.logoUrl || assetPath("/logo.png");
}

function isActive(pathname: string, href: string) {
  return pathname === href || (href !== "/" && pathname.startsWith(href));
}

export function SiteHeader({ settings }: { settings?: WebsiteSettings }) {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);
  const [expanded, setExpanded] = useState<string | null>(null);
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

        <nav className="hidden items-center gap-1 lg:flex">
          {PRIMARY.slice(0, 1).map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className={`rounded-full px-3 py-2 text-sm transition ${isActive(pathname, item.href) ? "bg-white/15 text-white" : "text-white/80 hover:bg-white/10 hover:text-white"}`}
            >
              {item.label}
            </Link>
          ))}
          {NAV_GROUPS.map((group) => (
            <div key={group.label} className="group relative">
              <button
                type="button"
                className="rounded-full px-3 py-2 text-sm text-white/80 transition hover:bg-white/10 hover:text-white"
              >
                {group.label} <span className="text-xs opacity-70">▾</span>
              </button>
              <div className="invisible absolute left-0 top-full z-50 min-w-[200px] pt-2 opacity-0 transition group-hover:visible group-hover:opacity-100">
                <div className="rounded-xl border border-white/10 bg-[var(--rk-purple-deep)] py-2 shadow-xl">
                  {group.items.map((item) => (
                    <Link
                      key={item.href}
                      href={item.href}
                      className={`block px-4 py-2 text-sm ${isActive(pathname, item.href) ? "bg-white/10 text-white" : "text-white/80 hover:bg-white/5 hover:text-white"}`}
                    >
                      {item.label}
                    </Link>
                  ))}
                </div>
              </div>
            </div>
          ))}
          {PRIMARY.slice(1).map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className={`rounded-full px-3 py-2 text-sm transition ${isActive(pathname, item.href) ? "bg-white/15 text-white" : "text-white/80 hover:bg-white/10 hover:text-white"}`}
            >
              {item.label}
            </Link>
          ))}
          <Link
            href="/admissions/apply"
            className="ml-2 rounded-full bg-[var(--rk-gold)] px-5 py-2 text-sm font-bold text-[var(--rk-purple-deep)] transition hover:brightness-110"
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
          {PRIMARY.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              onClick={() => setOpen(false)}
              className={`block rounded-lg px-2 py-2.5 ${isActive(pathname, item.href) ? "bg-white/10 font-semibold text-white" : "text-white/90"}`}
            >
              {item.label}
            </Link>
          ))}
          {NAV_GROUPS.map((group) => (
            <div key={group.label} className="mt-2">
              <button
                type="button"
                onClick={() => setExpanded(expanded === group.label ? null : group.label)}
                className="flex w-full items-center justify-between rounded-lg px-2 py-2.5 text-sm font-semibold text-[var(--rk-gold)]"
              >
                {group.label}
                <span>{expanded === group.label ? "▴" : "▾"}</span>
              </button>
              {expanded === group.label &&
                group.items.map((item) => (
                  <Link
                    key={item.href}
                    href={item.href}
                    onClick={() => setOpen(false)}
                    className="block rounded-lg py-2 pl-6 text-sm text-white/85"
                  >
                    {item.label}
                  </Link>
                ))}
            </div>
          ))}
          <Link
            href="/admissions/apply"
            onClick={() => setOpen(false)}
            className="mt-4 block rounded-full bg-[var(--rk-gold)] py-3 text-center text-sm font-bold text-[var(--rk-purple-deep)]"
          >
            Apply Now
          </Link>
          <div className="mt-4 flex justify-center">
            <SocialBar />
          </div>
        </div>
      )}
    </header>
  );
}

const CONTACT_LINE = "📍 Wangige, Kiambu · ☎ +254 719 396 233";
