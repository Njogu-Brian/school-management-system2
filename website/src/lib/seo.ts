import type { Metadata } from "next";
import { BRAND } from "@/content/schoolContent";

/** Canonical public site. Override with NEXT_PUBLIC_SITE_URL if the domain changes. */
export const SITE_URL = (process.env.NEXT_PUBLIC_SITE_URL || "https://royalkingsschools.sc.ke").replace(
  /\/$/,
  "",
);

export const SITE_NAME = BRAND.shortName;

type ChangeFrequency = "always" | "hourly" | "daily" | "weekly" | "monthly" | "yearly" | "never";

export type PublicPage = {
  path: string;
  /** Page title. The root layout appends the school name, except on the homepage. */
  title: string;
  description: string;
  priority: number;
  changeFrequency: ChangeFrequency;
};

export const PUBLIC_PAGES: PublicPage[] = [
  {
    path: "/",
    title: "Royal Kings Premier School | Creche to Grade 9 in Wangige",
    description:
      "Royal Kings Premier School is a Christian-centered school in Wangige, Kiambu. Creche through Grade 9, CBC, and 2027 admissions now open.",
    priority: 1,
    changeFrequency: "weekly",
  },
  {
    path: "/admissions",
    title: "Admissions",
    description:
      "Enrol at Royal Kings Premier School in Wangige. Campus tours, 2027 admissions, and how to join from Creche to Grade 9.",
    priority: 0.9,
    changeFrequency: "weekly",
  },
  {
    path: "/admissions/apply",
    title: "Apply",
    description: "Start an online application for Royal Kings Premier School. Creche, preschool, primary, and junior secondary.",
    priority: 0.9,
    changeFrequency: "weekly",
  },
  {
    path: "/admissions/faq",
    title: "Admissions FAQ",
    description:
      "Answers on ages, Christian ethos, fees, transport, and how to apply to Royal Kings Premier School in Wangige, Kiambu.",
    priority: 0.7,
    changeFrequency: "monthly",
  },
  {
    path: "/academics",
    title: "Academics",
    description:
      "Early years, primary, and junior secondary (CBC Grade 7–9) at Royal Kings Premier School, Wangige.",
    priority: 0.9,
    changeFrequency: "monthly",
  },
  {
    path: "/fees",
    title: "School Fees",
    description: "School fee information for Royal Kings Premier School. Contact admissions for the current fee structure.",
    priority: 0.8,
    changeFrequency: "monthly",
  },
  {
    path: "/about",
    title: "About",
    description:
      "Royal Kings Premier School has served families in Wangige since 2006. Our story, mission, and Christian-centered education.",
    priority: 0.8,
    changeFrequency: "monthly",
  },
  {
    path: "/leadership",
    title: "Leadership",
    description: "Meet the leadership of Royal Kings Premier School in Wangige, Kiambu.",
    priority: 0.6,
    changeFrequency: "monthly",
  },
  {
    path: "/campus-life",
    title: "Campus Life",
    description: "Campus photos, daily life, and events at Royal Kings Premier School in Wangige.",
    priority: 0.7,
    changeFrequency: "weekly",
  },
  {
    path: "/gallery",
    title: "Gallery",
    description: "Photo gallery of learners, classrooms, and campus life at Royal Kings Premier School.",
    priority: 0.6,
    changeFrequency: "weekly",
  },
  {
    path: "/community",
    title: "Community",
    description: "Co-curricular programmes, spotlights, and community life at Royal Kings Premier School.",
    priority: 0.7,
    changeFrequency: "weekly",
  },
  {
    path: "/calendar",
    title: "School Calendar",
    description: "Term dates, open days, and school events at Royal Kings Premier School, Wangige.",
    priority: 0.7,
    changeFrequency: "weekly",
  },
  {
    path: "/blog",
    title: "News",
    description: "News and stories from Royal Kings Premier School in Wangige, Kiambu.",
    priority: 0.6,
    changeFrequency: "weekly",
  },
  {
    path: "/transport",
    title: "School Transport",
    description:
      "School bus routes serving Lower Kabete, Wangige, Kibichiku, Karura, Ngecha, and nearby areas.",
    priority: 0.7,
    changeFrequency: "monthly",
  },
  {
    path: "/child-safety",
    title: "Child Safety",
    description:
      "Safeguarding, supervision, pickup, and visitor procedures at Royal Kings Premier School.",
    priority: 0.6,
    changeFrequency: "monthly",
  },
  {
    path: "/contact",
    title: "Contact",
    description:
      "Call, WhatsApp, or visit Royal Kings Premier School in Wangige, Kiambu County. Admissions and campus tours.",
    priority: 0.8,
    changeFrequency: "monthly",
  },
];

export function absoluteUrl(path: string): string {
  if (!path || path === "/") return SITE_URL;
  return `${SITE_URL}${path.startsWith("/") ? path : `/${path}`}`;
}

export function pageMetadata(path: string): Metadata {
  const page = PUBLIC_PAGES.find((item) => item.path === path);
  if (!page) {
    return { alternates: { canonical: absoluteUrl(path) } };
  }

  const url = absoluteUrl(page.path);
  const isHome = page.path === "/";
  const fullTitle = isHome ? page.title : `${page.title} | ${SITE_NAME}`;

  return {
    title: isHome ? { absolute: page.title } : page.title,
    description: page.description,
    alternates: { canonical: url },
    openGraph: {
      title: fullTitle,
      description: page.description,
      url,
      type: "website",
      locale: "en_KE",
      siteName: SITE_NAME,
    },
  };
}

/** Private flows (parent portal, application tracking) must stay out of Google. */
export const PRIVATE_PAGE_METADATA: Metadata = {
  robots: { index: false, follow: false },
};
