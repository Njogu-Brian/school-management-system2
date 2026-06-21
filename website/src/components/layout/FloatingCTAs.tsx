"use client";

import type { WebsiteSettings } from "@/types/website";
import { CONTACT } from "@/content/schoolContent";

function whatsappNumber(settings?: WebsiteSettings) {
  const raw = settings?.whatsapp || CONTACT.whatsapp;
  return raw.replace(/\D/g, "");
}

export function FloatingWhatsApp({ settings }: { settings?: WebsiteSettings }) {
  const number = whatsappNumber(settings);
  const href = `https://wa.me/${number}?text=${encodeURIComponent("Hello Royal Kings, I would like to enquire about admissions.")}`;

  return (
    <a
      href={href}
      target="_blank"
      rel="noopener noreferrer"
      className="rk-fab rk-fab-whatsapp group"
      aria-label="Chat on WhatsApp"
      title="Chat on WhatsApp"
    >
      <span className="rk-fab-pulse rk-fab-pulse-whatsapp" aria-hidden />
      <span className="relative z-10 text-2xl">💬</span>
      <span className="rk-fab-label">WhatsApp</span>
    </a>
  );
}

export function FloatingMaps({ settings }: { settings?: WebsiteSettings }) {
  const mapsUrl = settings?.google_map?.includes("http")
    ? CONTACT.mapsUrl
    : CONTACT.mapsUrl;

  return (
    <a
      href={mapsUrl}
      target="_blank"
      rel="noopener noreferrer"
      className="rk-fab rk-fab-maps group"
      aria-label="Find us on Google Maps"
      title="Find us on Google Maps"
    >
      <span className="rk-fab-pulse rk-fab-pulse-maps" aria-hidden />
      <span className="relative z-10 text-2xl">📍</span>
      <span className="rk-fab-label">Find Us</span>
    </a>
  );
}

export function StickyAdmissionsButton({ settings }: { settings?: WebsiteSettings }) {
  if (settings?.admissions_open === false) return null;

  return (
    <a href="/admissions" className="rk-fab-admissions">
      Enroll Now
    </a>
  );
}
