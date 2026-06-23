"use client";

import { useQuery } from "@tanstack/react-query";
import { websiteService } from "@/services/websiteService";
import { galleryItemsFromResponse } from "@/lib/premiumMedia";

export function useHeroMedia() {
  return useQuery({
    queryKey: ["website", "media", "hero"],
    queryFn: websiteService.getHeroMedia,
    staleTime: 5 * 60 * 1000,
  });
}

export function usePremiumGallery(params?: { category?: string; per_page?: number }) {
  return useQuery({
    queryKey: ["website", "gallery", "premium", params],
    queryFn: async () => {
      const res = await websiteService.getGallery({ ...params, premium: true, per_page: params?.per_page ?? 24 });
      return galleryItemsFromResponse(res);
    },
    staleTime: 5 * 60 * 1000,
  });
}

export function usePremiumTestimonials() {
  return useQuery({
    queryKey: ["website", "testimonials", "premium"],
    queryFn: () => websiteService.getTestimonials(true),
    staleTime: 5 * 60 * 1000,
  });
}
