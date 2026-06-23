import { api } from "@/lib/api";
import type {
  ApiResponse,
  BlogPost,
  FaqItem,
  GalleryItem,
  HomepageData,
  Testimonial,
  WebsiteEvent,
  WebsitePage,
  WebsiteSettings,
} from "@/types/website";

export const websiteService = {
  getSettings: () =>
    api.get<ApiResponse<WebsiteSettings>>("/website/settings").then((r) => r.data.data),

  getHomepage: () =>
    api.get<ApiResponse<HomepageData>>("/website/homepage").then((r) => r.data.data),

  getPage: (slug: string) =>
    api.get<ApiResponse<WebsitePage>>(`/website/pages/${slug}`).then((r) => r.data.data),

  getBlogs: (params?: { search?: string; per_page?: number }) =>
    api.get<{ data: BlogPost[] }>("/website/blogs", { params }).then((r) => r.data),

  getBlog: (slug: string) =>
    api.get<ApiResponse<BlogPost>>(`/website/blogs/${slug}`).then((r) => r.data.data),

  getEvents: (upcoming = true) =>
    api.get<ApiResponse<WebsiteEvent[]>>("/website/events", { params: { upcoming } }).then((r) => r.data.data),

  getEvent: (slug: string) =>
    api.get<ApiResponse<WebsiteEvent>>(`/website/events/${slug}`).then((r) => r.data.data),

  getTestimonials: (premium = false) =>
    api
      .get<ApiResponse<Testimonial[]>>("/website/testimonials", { params: { premium: premium ? 1 : 0 } })
      .then((r) => r.data.data),

  getGallery: (params?: { category?: string; featured?: boolean; premium?: boolean; hero?: boolean; per_page?: number }) =>
    api.get<{ data: GalleryItem[] }>("/website/gallery", { params }).then((r) => r.data),

  getHeroMedia: () =>
    api.get<ApiResponse<GalleryItem | null>>("/website/media/hero").then((r) => r.data.data),

  getFaqs: (category?: string) =>
    api.get<ApiResponse<FaqItem[]>>("/website/faqs", { params: { category } }).then((r) => r.data.data),

  getBrand: () =>
    api.get<{ data: import("@/types/brand").BrandContent }>("/website/brand").then((r) => r.data.data),

  submitEnquiry: (payload: Record<string, string>) =>
    api.post("/website/enquiry", payload).then((r) => r.data),
};
