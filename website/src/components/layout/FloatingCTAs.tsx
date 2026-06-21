"use client";

import type { WebsiteSettings } from "@/types/website";

export function FloatingWhatsApp({ settings }: { settings?: WebsiteSettings }) {
  if (!settings?.whatsapp) return null;

  const href = `https://wa.me/${settings.whatsapp.replace(/\D/g, "")}`;

  return (
    <a
      href={href}
      target="_blank"
      rel="noopener noreferrer"
      className="fixed bottom-24 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-2xl text-white shadow-2xl transition hover:scale-110"
      aria-label="Chat on WhatsApp"
    >
      💬
    </a>
  );
}

export function StickyAdmissionsButton({ settings }: { settings?: WebsiteSettings }) {
  if (!settings?.admissions_open) return null;

  return (
    <a
      href="/admissions"
      className="fixed bottom-6 right-6 z-50 rounded-full bg-[#D4AF37] px-5 py-3 text-sm font-bold text-[#2a1145] shadow-2xl transition hover:scale-105"
    >
      Admissions Open
    </a>
  );
}
