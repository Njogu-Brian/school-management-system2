"use client";

import { useEffect } from "react";
import { usePathname } from "next/navigation";
import { analyticsService } from "@/services/analyticsService";

export function AnalyticsTracker() {
  const pathname = usePathname();

  useEffect(() => {
    const start = Date.now();
    analyticsService.trackPageView(pathname);
    return () => {
      analyticsService.trackPageView(pathname, Math.round((Date.now() - start) / 1000));
    };
  }, [pathname]);

  return null;
}
