import Link from "next/link";
import type { WebsiteSettings } from "@/types/website";

export function SiteFooter({ settings }: { settings?: WebsiteSettings }) {
  return (
    <footer className="bg-[#1a0a2e] text-white">
      <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 md:grid-cols-3 lg:px-8">
        <div>
          <h3 className="font-serif text-xl text-[#D4AF37]">{settings?.school_name || "Royal Kings Education Centre"}</h3>
          <p className="mt-3 text-sm text-white/70">{settings?.tagline}</p>
          <p className="mt-4 text-sm text-white/70">{settings?.address}</p>
        </div>
        <div>
          <h4 className="font-semibold text-[#D4AF37]">Quick Links</h4>
          <div className="mt-3 flex flex-col gap-2 text-sm text-white/80">
            <Link href="/admissions">Admissions</Link>
            <Link href="/academics">Academics</Link>
            <Link href="/contact">Contact</Link>
            <Link href="/parent-portal">Parent Portal</Link>
          </div>
        </div>
        <div>
          <h4 className="font-semibold text-[#D4AF37]">Contact</h4>
          <p className="mt-3 text-sm text-white/80">{settings?.phone}</p>
          <p className="text-sm text-white/80">{settings?.email}</p>
        </div>
      </div>
      <div className="border-t border-white/10 py-4 text-center text-xs text-white/50">
        © {new Date().getFullYear()} Royal Kings Education Centre. All rights reserved.
      </div>
    </footer>
  );
}
