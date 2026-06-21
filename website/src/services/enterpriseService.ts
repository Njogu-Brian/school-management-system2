import { api } from "@/lib/api";

export const enterpriseService = {
  assistantChat: (message: string, sessionKey?: string, pagePath?: string) =>
    api.post("/website/assistant/chat", { message, session_key: sessionKey, page_path: pagePath }).then((r) => r.data),

  showcase: () => api.get("/website/showcase").then((r) => r.data),

  live: () => api.get("/website/live").then((r) => r.data),

  liveStatus: () => api.get("/website/live/status").then((r) => r.data),

  community: () => api.get("/website/community").then((r) => r.data),

  submitReferral: (payload: Record<string, string>) =>
    api.post("/website/community/referrals", payload).then((r) => r.data),

  submitPrayer: (payload: Record<string, string | boolean>) =>
    api.post("/website/community/prayer-requests", payload).then((r) => r.data),
};
