"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import type { WebsiteSettings } from "@/types/website";
import { BRAND } from "@/content/schoolContent";
import { SocialBar } from "@/components/layout/SocialBar";
import { assetPath } from "@/lib/assetPath";
import { getUsersAppUrl } from "@/lib/erpUrls";
import { EditableText } from "@/components/live-editor/EditableText";
import { EditableImage } from "@/components/live-editor/EditableImage";
import { useLiveText } from "@/components/live-editor/LiveEditorProvider";

const NAV_GROUPS = [
  {
    label: "Admissions",
    items: [
      { href: "/admissions/apply", label: "Apply" },
      { href: "/fees", label: "Fees" },
      { href: "/calendar", label: "Calendar" },
      { href: "/admissions/faq", label: "FAQ" },
    ],
  },
  {
    label: "Life at Royal Kings",
    items: [
      { href: "/co-curricular", label: "Co-Curricular" },
      { href: "/calendar", label: "Events" },
      { href: "/campus-life", label: "Gallery" },
      { href: "/community", label: "Community" },
    ],
  },
  {
    label: "About",
    items: [
      { href: "/leadership", label: "Leadership" },
      { href: "/child-safety", label: "Child Safety" },
      { href: "/transport", label: "Transport" },
      { href: "/contact", label: "Contact" },
    ],
  },
];

function logoSrc(settings?: WebsiteSettings) {
  return settings?.logo || BRAND.logoUrl || assetPath("/logo.png");
}

function isActive(pathname: string, href: string) {
  const base = href.split("#")[0];
  return pathname === base || (base !== "/" && pathname.startsWith(base));
}

export function SiteHeader({ settings }: { settings?: WebsiteSettings }) {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);
  const [expanded, setExpanded] = useState<string | null>(null);
  const schoolName = useLiveText("chrome.schoolName", settings?.school_name || BRAND.shortName);
  const tagline = useLiveText("chrome.tagline", settings?.tagline || BRAND.tagline);
  const contactLine = useLiveText("chrome.contactLine", CONTACT_LINE);

  return (
    <header className="sticky top-0 z-50 border-b border-white/10 bg-rk-deep-purple/95 backdrop-blur-md">
      <div className="border-b border-white/5 bg-rk-purple-dark/80">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-1.5 text-xs text-white/80 lg:px-8">
          <EditableText editKey="chrome.contactLine" as="span" className="hidden sm:inline">
            {contactLine}
          </EditableText>
          <SocialBar compact />
        </div>
      </div>

      <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 lg:px-8">
        <Link href="/" className="flex items-center gap-3">
          <EditableImage
            editKey="chrome.logo"
            src={logoSrc(settings)}
            alt={`${schoolName} logo`}
            className="h-11 w-11 rounded-full bg-white object-contain p-0.5 shadow-md ring-2 ring-rk-gold"
          />
          <div className="min-w-0">
            <EditableText
              editKey="chrome.schoolName"
              as="p"
              className="truncate font-serif text-base font-semibold text-white sm:text-lg"
            >
              {schoolName}
            </EditableText>
            <EditableText editKey="chrome.tagline" as="p" className="truncate text-xs text-rk-gold">
              {tagline}
            </EditableText>
          </div>
        </Link>

        <nav className="hidden items-center gap-0.5 lg:flex xl:gap-1" aria-label="Main">
          <Link
            href="/"
            className={`rounded-full px-3 py-2 text-sm transition ${isActive(pathname, "/") ? "bg-white/15 text-white" : "text-white/80 hover:bg-white/10 hover:text-white"}`}
          >
            Home
          </Link>
          {NAV_GROUPS.map((group) => (
            <div key={group.label} className="group relative">
              <button
                type="button"
                className="rounded-full px-3 py-2 text-sm text-white/80 transition hover:bg-white/10 hover:text-white"
              >
                {group.label} <span className="text-xs opacity-70">▾</span>
              </button>
              <div className="invisible absolute left-0 top-full z-50 min-w-[210px] pt-2 opacity-0 transition group-hover:visible group-hover:opacity-100">
                <div className="rounded-rk-lg border border-white/10 bg-rk-deep-purple py-2 shadow-rk-xl">
                  {group.items.map((item) => (
                    <Link
                      key={item.href}
                      href={item.href}
                      className={`block px-4 py-2.5 text-sm ${isActive(pathname, item.href) ? "bg-white/10 text-white" : "text-white/80 hover:bg-white/5 hover:text-white"}`}
                    >
                      {item.label}
                    </Link>
                  ))}
                </div>
              </div>
            </div>
          ))}
          <a
            href={getUsersAppUrl()}
            target="_blank"
            rel="noreferrer"
            className="rounded-full px-3 py-2 text-sm text-white/80 transition hover:bg-white/10 hover:text-white"
          >
            Parents
          </a>
          <Link
            href="/admissions/apply"
            className="ml-2 rounded-full bg-rk-gold px-5 py-2 text-sm font-bold text-rk-deep-purple transition hover:brightness-110"
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
          <Link href="/" onClick={() => setOpen(false)} className="block rounded-lg px-2 py-2.5 text-white/90">
            Home
          </Link>
          {NAV_GROUPS.map((group) => (
            <div key={group.label} className="mt-2">
              <button
                type="button"
                onClick={() => setExpanded(expanded === group.label ? null : group.label)}
                className="flex w-full items-center justify-between rounded-lg px-2 py-2.5 text-sm font-semibold text-rk-gold"
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
          <a href={getUsersAppUrl()} target="_blank" rel="noreferrer" onClick={() => setOpen(false)} className="mt-2 block rounded-lg px-2 py-2.5 text-white/90">
            Parents
          </a>
          <Link
            href="/admissions/apply"
            onClick={() => setOpen(false)}
            className="mt-4 block rounded-full bg-rk-gold py-3 text-center text-sm font-bold text-rk-deep-purple"
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
