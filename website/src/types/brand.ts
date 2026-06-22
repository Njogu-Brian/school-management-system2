export interface BrandItem {
  id?: number;
  title?: string;
  subtitle?: string;
  body?: string;
  image_url?: string;
  link_url?: string;
  video_url?: string;
  settings?: Record<string, unknown>;
  sort_order?: number;
}

export type BrandContent = Record<string, BrandItem[]>;
