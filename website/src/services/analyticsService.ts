import { api } from "@/lib/api";

const VISITOR_KEY = "rk_visitor_id";

function visitorId(): string {
  if (typeof window === "undefined") return "server";
  let id = localStorage.getItem(VISITOR_KEY);
  if (!id) {
    id = crypto.randomUUID();
    localStorage.setItem(VISITOR_KEY, id);
  }
  return id;
}

export const analyticsService = {
  trackPageView: (page: string, duration = 0) =>
    api.post("/website/analytics/page-view", {
      page,
      visitor_id: visitorId(),
      device: typeof navigator !== "undefined" ? navigator.userAgent.slice(0, 100) : undefined,
      duration,
    }).catch(() => undefined),

  trackEvent: (eventType: string, page?: string, metadata?: Record<string, unknown>) =>
    api.post("/website/analytics/event", {
      event_type: eventType,
      page,
      visitor_id: visitorId(),
      metadata,
    }).catch(() => undefined),
};
