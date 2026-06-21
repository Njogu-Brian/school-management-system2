"use client";

import type { WebsiteSettings } from "@/types/website";
import { CONTACT } from "@/content/schoolContent";
import { GoogleMapsIcon, WhatsAppIcon } from "@/components/icons/BrandIcons";

function whatsappNumber(settings?: WebsiteSettings) {
  const raw = settings?.whatsapp || CONTACT.whatsapp;
  return raw.replace(/\D/g, "");
}

export function FloatingWhatsApp({ settings }: { settings?: WebsiteSettings }) {
  const number = whatsappNumber(settings);
  const href = `https://wa.me/${number}?text=${encodeURIComponent("Hello Royal Kings Premier School, I would like to enquire about admissions.")}`;

  return (
    <a
      href={href}
      target="_blank"
      rel="noopener noreferrer"
      className="rk-fab rk-fab-whatsapp group"
      aria-label="Chat on WhatsApp"
      title="Chat on WhatsApp"
    >
      <span className="rk-fab-ring rk-fab-ring-whatsapp" aria-hidden />
      <span className="rk-fab-ring rk-fab-ring-whatsapp rk-fab-ring-delay" aria-hidden />
      <span className="relative z-10 flex items-center justify-center text-white">
        <WhatsAppIcon className="h-8 w-8" />
      </span>
      <span className="rk-fab-label">WhatsApp Us</span>
    </a>
  );
}

export function FloatingMaps({ settings }: { settings?: WebsiteSettings }) {
  void settings;
  return (
    <a
      href={CONTACT.mapsUrl}
      target="_blank"
      rel="noopener noreferrer"
      className="rk-fab rk-fab-maps group"
      aria-label="Find us on Google Maps"
      title="Find us on Google Maps"
    >
      <span className="rk-fab-ring rk-fab-ring-maps" aria-hidden />
      <span className="rk-fab-ring rk-fab-ring-maps rk-fab-ring-delay" aria-hidden />
      <span className="relative z-10 flex items-center justify-center rounded-full bg-white p-1.5 shadow-inner">
        <GoogleMapsIcon className="h-7 w-7" />
      </span>
      <span className="rk-fab-label">Google Maps</span>
    </a>
  );
}

export function StickyAdmissionsButton({ settings }: { settings?: WebsiteSettings }) {
  if (settings?.admissions_open === false) return null;

  return (
    <a href="/admissions" className="rk-fab-admissions animate-rk-bounce-subtle">
      Enroll Now
    </a>
  );
}
