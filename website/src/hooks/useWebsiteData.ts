"use client";

import { useQuery } from "@tanstack/react-query";
import { galleryItemsFromResponse } from "@/lib/premiumMedia";
import { websiteService } from "@/services/websiteService";

export function useWebsiteSettings() {
  return useQuery({
    queryKey: ["website", "settings"],
    queryFn: websiteService.getSettings,
    staleTime: 5 * 60 * 1000,
  });
}

export function useHomepage() {
  return useQuery({
    queryKey: ["website", "homepage"],
    queryFn: websiteService.getHomepage,
    staleTime: 2 * 60 * 1000,
  });
}

export function useWebsitePage(slug: string) {
  return useQuery({
    queryKey: ["website", "page", slug],
    queryFn: () => websiteService.getPage(slug),
    enabled: !!slug,
  });
}

export function useBlogs(search?: string) {
  return useQuery({
    queryKey: ["website", "blogs", search],
    queryFn: () => websiteService.getBlogs({ search }),
  });
}

export function useEvents() {
  return useQuery({
    queryKey: ["website", "events"],
    queryFn: () => websiteService.getEvents(true),
  });
}

export function useTestimonials(premium = false) {
  return useQuery({
    queryKey: ["website", "testimonials", premium ? "premium" : "all"],
    queryFn: () => websiteService.getTestimonials(premium),
  });
}

export function useGallery(category?: string, options?: { premium?: boolean; hero?: boolean }) {
  return useQuery({
    queryKey: ["website", "gallery", category, options],
    queryFn: async () => {
      const res = await websiteService.getGallery({
        category,
        premium: options?.premium,
        hero: options?.hero,
      });
      return galleryItemsFromResponse(res);
    },
  });
}

export function useFaqs(category?: string) {
  return useQuery({
    queryKey: ["website", "faqs", category],
    queryFn: () => websiteService.getFaqs(category),
  });
}
