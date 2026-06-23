import { LEGACY_IMAGES } from "@/content/schoolContent";

export interface SchoolPathway {
  id?: string | number;
  title: string;
  subtitle: string;
  body: string;
  ctaLabel: string;
  linkUrl: string;
  imageUrl?: string;
  srcset?: string;
}

export const FIND_YOUR_PLACE_TITLE = "Find Your Child's Place";

export const FIND_YOUR_PLACE_SUBTITLE =
  "Three pathways, one caring community — help your child find where they belong from their very first day.";

export const DEFAULT_SCHOOL_PATHWAYS: SchoolPathway[] = [
  {
    id: "early-years",
    title: "Creche & Early Years",
    subtitle: "Age 3–5",
    body: "Strong beginnings through play, care, and foundational learning.",
    ctaLabel: "Explore Early Years",
    linkUrl: "/academics#early-years",
    imageUrl: LEGACY_IMAGES.family2,
  },
  {
    id: "primary",
    title: "Primary School",
    subtitle: "Grade 1–6",
    body: "Academic growth, creativity, and discovery.",
    ctaLabel: "Explore Primary",
    linkUrl: "/academics#primary",
    imageUrl: LEGACY_IMAGES.classroom,
  },
  {
    id: "junior-secondary",
    title: "Junior Secondary",
    subtitle: "Grade 7–9",
    body: "Leadership, discipline, and future readiness.",
    ctaLabel: "Explore Junior School",
    linkUrl: "/academics#junior-secondary",
    imageUrl: LEGACY_IMAGES.students,
  },
];
