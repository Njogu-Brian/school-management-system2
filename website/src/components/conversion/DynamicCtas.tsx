"use client";

import { useEffect, useState } from "react";
import { usePathname } from "next/navigation";
import { conversionService, type ManagedCta } from "@/services/conversionService";

export function DynamicCtas() {
  const pathname = usePathname();
  const [ctas, setCtas] = useState<ManagedCta[]>([]);

  useEffect(() => {
    conversionService.getCtas(pathname).then(setCtas).catch(() => setCtas([]));
  }, [pathname]);

  if (!ctas.length) return null;

  return (
    <div className="fixed bottom-36 left-4 z-40 flex flex-col gap-2">
      {ctas.map((cta) => (
        <a
          key={cta.id}
          href={cta.url || "/admissions"}
          onClick={() => conversionService.trackCtaClick(cta.id, pathname).catch(() => {})}
          className="rounded-full bg-[#5B2C8E] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:bg-[#4a2475]"
        >
          {cta.label}
        </a>
      ))}
    </div>
  );
}
