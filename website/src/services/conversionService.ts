import { api } from "@/lib/api";

export type ManagedCta = { id: number; type: string; label: string; url?: string };
export type ExitIntentCampaign = {
  id: number;
  title: string;
  message?: string;
  button_label: string;
  button_url?: string;
};

export const conversionService = {
  getCtas: (page?: string) =>
    api.get<{ data: ManagedCta[] }>("/website/conversion/ctas", { params: { page } }).then((r) => r.data.data),

  trackCtaClick: (ctaId: number, page?: string, visitorId?: string) =>
    api.post("/website/conversion/cta-click", { cta_id: ctaId, page, visitor_id: visitorId }),

  getExitIntent: (page?: string) =>
    api
      .get<{ data: ExitIntentCampaign | null }>("/website/conversion/exit-intent", { params: { page } })
      .then((r) => r.data.data),

  recordExitConversion: (campaignId: number) =>
    api.post("/website/conversion/exit-intent/convert", { campaign_id: campaignId }),

  getLeadMagnets: () =>
    api.get<{ data: { id: number; title: string; slug: string; description?: string }[] }>("/website/conversion/lead-magnets").then((r) => r.data.data),

  downloadLeadMagnet: (slug: string, payload: { name: string; email: string; phone?: string }) =>
    api.post(`/website/conversion/lead-magnets/${slug}/download`, payload).then((r) => r.data),
};
