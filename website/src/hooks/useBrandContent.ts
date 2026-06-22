"use client";

import { useQuery } from "@tanstack/react-query";
import { websiteService } from "@/services/websiteService";
import { BRAND_DEFAULTS } from "@/content/brandDefaults";
import type { BrandContent, BrandItem } from "@/types/brand";

function mergeBrand(api: BrandContent | undefined): BrandContent {
  const out: BrandContent = { ...BRAND_DEFAULTS };
  if (!api) return out;
  for (const [key, items] of Object.entries(api)) {
    if (items?.length) out[key] = items;
  }
  return out;
}

export function useBrandContent() {
  const query = useQuery({
    queryKey: ["website", "brand"],
    queryFn: websiteService.getBrand,
    staleTime: 5 * 60 * 1000,
  });

  const data = mergeBrand(query.data);

  return {
    ...query,
    data,
    items: (type: string): BrandItem[] => data[type] ?? [],
  };
}
