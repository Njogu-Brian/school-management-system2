"use client";

import { useEffect, useState } from "react";
import { usePathname } from "next/navigation";
import { conversionService, type ExitIntentCampaign } from "@/services/conversionService";

export function ExitIntentModal() {
  const pathname = usePathname();
  const [campaign, setCampaign] = useState<ExitIntentCampaign | null>(null);
  const [open, setOpen] = useState(false);
  const [shown, setShown] = useState(false);

  useEffect(() => {
    conversionService.getExitIntent(pathname).then(setCampaign).catch(() => setCampaign(null));
  }, [pathname]);

  useEffect(() => {
    if (!campaign || shown) return;

    const onLeave = (e: MouseEvent) => {
      if (e.clientY <= 0) {
        setOpen(true);
        setShown(true);
      }
    };

    document.addEventListener("mouseout", onLeave);
    return () => document.removeEventListener("mouseout", onLeave);
  }, [campaign, shown]);

  if (!open || !campaign) return null;

  const close = () => setOpen(false);

  const convert = () => {
    conversionService.recordExitConversion(campaign.id).catch(() => {});
    if (campaign.button_url) window.location.href = campaign.button_url;
    close();
  };

  return (
    <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4">
      <div className="max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h3 className="font-serif text-xl text-[#5B2C8E]">{campaign.title}</h3>
        {campaign.message && <p className="mt-2 text-[#4a3a5c]">{campaign.message}</p>}
        <div className="mt-4 flex gap-2">
          <button type="button" onClick={convert} className="rounded-full bg-[#D4AF37] px-4 py-2 text-sm font-bold text-[#2a1145]">
            {campaign.button_label}
          </button>
          <button type="button" onClick={close} className="rounded-full border px-4 py-2 text-sm text-[#4a3a5c]">
            Maybe later
          </button>
        </div>
      </div>
    </div>
  );
}
