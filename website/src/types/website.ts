export interface WebsiteSettings {
  school_name: string;
  tagline: string;
  primary_color: string;
  secondary_color: string;
  phone: string;
  email: string;
  address: string;
  google_map: string;
  whatsapp: string;
  social: {
    facebook?: string;
    instagram?: string;
    youtube?: string;
    tiktok?: string;
  };
  hero_video?: string;
  logo?: string;
  favicon?: string;
  admissions_open: boolean;
  current_term?: string;
  seo: {
    title?: string;
    description?: string;
    keywords?: string;
    og_image?: string;
  };
}

export interface PageSection {
  type: string;
  key: string;
  title?: string;
  subtitle?: string;
  content?: string;
  settings?: Record<string, unknown>;
  sort_order: number;
}

export interface WebsitePage {
  id: number;
  name: string;
  slug: string;
  title: string;
  is_homepage: boolean;
  published_at?: string;
  seo: { title?: string; description?: string };
  sections?: PageSection[];
}

export interface HomepageData {
  page: WebsitePage;
  live_stats: {
    total_learners: number;
    class_structure: Array<{
      id: number;
      name: string;
      level?: string;
      academic_group?: string;
      campus?: string;
      learners: number;
    }>;
  };
  announcements: Array<{
    id: number;
    title: string;
    content: string;
    published_at?: string;
  }>;
  achievements: Array<{
    student: string;
    classroom?: string;
    award?: string;
    description?: string;
    date?: string;
  }>;
}

export interface BlogPost {
  id: number;
  title: string;
  slug: string;
  excerpt?: string;
  body?: string;
  featured_image?: string;
  author?: string;
  published_at?: string;
}

export interface WebsiteEvent {
  id: number;
  title: string;
  slug: string;
  description?: string;
  start_date: string;
  end_date?: string;
  cover_image?: string;
  location?: string;
  registration_enabled: boolean;
  source?: string;
}

export interface Testimonial {
  id: number;
  name: string;
  relationship?: string;
  message: string;
  photo?: string;
  video_url?: string;
  featured: boolean;
}

export interface GalleryItem {
  id: number;
  title: string;
  url: string;
  type: string;
  category?: string;
  alt_text?: string;
  is_featured: boolean;
}

export interface FaqItem {
  id: number;
  question: string;
  answer: string;
  category?: string;
  order: number;
}

export interface AgeJourneyStep {
  age: number;
  level: string;
  classroom: string;
  activities: string[];
  milestones: string[];
}

export interface ApiResponse<T> {
  data: T;
}
