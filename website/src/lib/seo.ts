import type { Metadata } from "next";
import { BRAND, CONTACT, SOCIAL } from "@/content/schoolContent";

/** Canonical public site. Override with NEXT_PUBLIC_SITE_URL if the domain changes. */
export const SITE_URL = (process.env.NEXT_PUBLIC_SITE_URL || "https://royalkingsschools.sc.ke").replace(
  /\/$/,
  "",
);

export const SITE_NAME = BRAND.shortName;

type ChangeFrequency = "always" | "hourly" | "daily" | "weekly" | "monthly" | "yearly" | "never";

export type PublicPage = {
  path: string;
  title: string;
  description: string;
  priority: number;
  changeFrequency: ChangeFrequency;
  h1?: string;
};

export const PUBLIC_PAGES: PublicPage[] = [
  {
    path: "/",
    title: "Royal Kings School | Preschool to Grade 9 in Wangige",
    description:
      "Royal Kings Premier School in Riverside, Wangige offers Christian-centred CBC education from preschool through Grade 9. Discover our Early Years, Primary and Junior School programmes. Book a school visit today.",
    h1: "Preschool, Primary and Junior School in Wangige, Kiambu County",
    priority: 1,
    changeFrequency: "weekly",
  },
  {
    path: "/preschool-wangige",
    title: "Preschool & Kindergarten in Wangige | Royal Kings School",
    description:
      "Discover Creche, Foundation, PP1 and PP2 at Royal Kings School in Riverside, Wangige. Give your child a nurturing start through early childhood learning and development.",
    h1: "A Nurturing Preschool and Early Years Learning Environment in Wangige",
    priority: 0.95,
    changeFrequency: "monthly",
  },
  {
    path: "/creche-early-years",
    title: "Creche & Early Years in Wangige | Royal Kings School",
    description:
      "Creche and Early Years at Royal Kings Premier School in Wangige. Play-based learning, school readiness, and Christian care for ages 3–5.",
    h1: "Creche and Early Years at Royal Kings in Wangige",
    priority: 0.9,
    changeFrequency: "monthly",
  },
  {
    path: "/primary-school-wangige",
    title: "Private Primary School in Wangige | Royal Kings School",
    description:
      "Explore CBC primary education at Royal Kings School in Wangige, Kiambu County. Discover Grades 1–6, dedicated teachers, learner development and enriching co-curricular activities.",
    h1: "Primary School Education in Wangige – Grades 1 to 6",
    priority: 0.95,
    changeFrequency: "monthly",
  },
  {
    path: "/junior-school-wangige",
    title: "Junior School Grade 7–9 in Wangige | Royal Kings",
    description:
      "Discover Grade 7, Grade 8 and Grade 9 at Royal Kings Junior School in Wangige. Experience CBC-aligned learning, practical skills, leadership and talent development.",
    h1: "Junior School Education in Wangige – Grades 7 to 9",
    priority: 0.95,
    changeFrequency: "monthly",
  },
  {
    path: "/admissions",
    title: "School Admissions in Wangige, Kiambu | Royal Kings",
    description:
      "Join Royal Kings Premier School in Wangige. Enquire about preschool, primary and junior school admissions. Contact our admissions team or arrange a school visit.",
    h1: "Admissions at Royal Kings Premier School",
    priority: 0.9,
    changeFrequency: "weekly",
  },
  {
    path: "/admissions/apply",
    title: "Apply Online",
    description:
      "Start an online application for Royal Kings Premier School. Preschool, primary, and junior school admissions in Wangige.",
    priority: 0.85,
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
    path: "/about",
    title: "About Royal Kings Premier School | Wangige, Kiambu",
    description:
      "Learn about Royal Kings Premier School, our educational journey, Christian values, teaching philosophy and commitment to developing confident and responsible learners.",
    priority: 0.8,
    changeFrequency: "monthly",
  },
  {
    path: "/academics",
    title: "Academics | CBC from Creche to Grade 9",
    description:
      "Early years, primary, and junior secondary (CBC Grade 7–9) at Royal Kings Premier School, Wangige.",
    priority: 0.85,
    changeFrequency: "monthly",
  },
  {
    path: "/fees",
    title: "School Fees in Wangige | Royal Kings",
    description:
      "School fee information for Royal Kings Premier School in Wangige. Contact admissions for the current fee structure and payment guidance.",
    priority: 0.8,
    changeFrequency: "monthly",
  },
  {
    path: "/co-curricular",
    title: "Co-curricular Activities | Royal Kings Wangige",
    description:
      "Ballet, skating, coding, robotics, music, sports and more at Royal Kings Premier School in Wangige.",
    priority: 0.75,
    changeFrequency: "monthly",
  },
  {
    path: "/transport",
    title: "School Transport in Wangige & Lower Kabete",
    description:
      "School bus routes serving Wangige, Lower Kabete, Kibichiku, Karura, Ngecha, Gathiga and nearby areas from Royal Kings Premier School.",
    priority: 0.75,
    changeFrequency: "monthly",
  },
  {
    path: "/gallery",
    title: "School Gallery | Royal Kings Wangige",
    description:
      "Authentic photos of learners, classrooms, graduations and campus life at Royal Kings Premier School in Wangige.",
    priority: 0.6,
    changeFrequency: "weekly",
  },
  {
    path: "/campus-life",
    title: "Campus Life | Royal Kings Wangige",
    description: "Campus photos, daily life, and events at Royal Kings Premier School in Wangige.",
    priority: 0.7,
    changeFrequency: "weekly",
  },
  {
    path: "/blog",
    title: "News & Stories | Royal Kings School",
    description: "News and educational stories from Royal Kings Premier School in Wangige, Kiambu.",
    priority: 0.65,
    changeFrequency: "weekly",
  },
  {
    path: "/contact",
    title: "Contact Royal Kings School | Riverside, Wangige",
    description:
      "Contact Royal Kings Premier School in Riverside, Wangige, Kiambu County. Enquire about admissions, school fees, transport and arranging a school visit.",
    priority: 0.85,
    changeFrequency: "monthly",
  },
  {
    path: "/leadership",
    title: "Leadership",
    description: "Meet the leadership of Royal Kings Premier School in Wangige, Kiambu.",
    priority: 0.55,
    changeFrequency: "monthly",
  },
  {
    path: "/community",
    title: "Community",
    description: "Co-curricular programmes, spotlights, and community life at Royal Kings Premier School.",
    priority: 0.6,
    changeFrequency: "weekly",
  },
  {
    path: "/calendar",
    title: "School Calendar",
    description: "Term dates, open days, and school events at Royal Kings Premier School, Wangige.",
    priority: 0.65,
    changeFrequency: "weekly",
  },
  {
    path: "/child-safety",
    title: "Child Safety",
    description:
      "Safeguarding, supervision, pickup, and visitor procedures at Royal Kings Premier School.",
    priority: 0.55,
    changeFrequency: "monthly",
  },
  {
    path: "/parent-info",
    title: "Parent Information | Royal Kings School Wangige",
    description:
      "Clear answers for parents: what Royal Kings offers, where we are, classes from preschool to Grade 9, admissions, school visits, fees and transport.",
    priority: 0.8,
    changeFrequency: "monthly",
  },
];

export function absoluteUrl(path: string): string {
  if (!path || path === "/") return SITE_URL;
  return `${SITE_URL}${path.startsWith("/") ? path : `/${path}`}`;
}

export function pageMetadata(path: string, overrides?: Partial<Metadata>): Metadata {
  const page = PUBLIC_PAGES.find((item) => item.path === path);
  if (!page) {
    return {
      alternates: { canonical: absoluteUrl(path) },
      ...overrides,
    };
  }

  const url = absoluteUrl(page.path);
  const isHome = page.path === "/";
  const fullTitle = isHome || page.title.includes("|") ? page.title : `${page.title} | ${SITE_NAME}`;

  return {
    title: isHome || page.title.includes("|") ? { absolute: page.title } : page.title,
    description: page.description,
    alternates: { canonical: url },
    openGraph: {
      title: fullTitle,
      description: page.description,
      url,
      type: "website",
      locale: "en_KE",
      siteName: SITE_NAME,
      images: [{ url: BRAND.logoUrl, alt: SITE_NAME }],
    },
    twitter: {
      card: "summary_large_image",
      title: fullTitle,
      description: page.description,
      images: [BRAND.logoUrl],
    },
    ...overrides,
  };
}

export const PRIVATE_PAGE_METADATA: Metadata = {
  robots: { index: false, follow: false },
};

export function schoolJsonLd() {
  return {
    "@context": "https://schema.org",
    "@type": ["School", "EducationalOrganization"],
    "@id": `${SITE_URL}/#school`,
    name: BRAND.shortName,
    legalName: BRAND.legalName,
    alternateName: [BRAND.name, "Royal Kings School", "Royal Kings School Wangige"],
    url: `${SITE_URL}/`,
    logo: BRAND.logoUrl,
    image: BRAND.logoUrl,
    description:
      "Royal Kings Premier School provides preschool, primary and junior school education in Riverside, Wangige, Kiambu County, Kenya.",
    foundingDate: String(BRAND.founded),
    telephone: CONTACT.phone,
    email: CONTACT.email,
    slogan: BRAND.tagline,
    address: {
      "@type": "PostalAddress",
      streetAddress: "Riverside, Wangige",
      addressLocality: "Wangige",
      addressRegion: "Kiambu County",
      postalCode: "00100",
      addressCountry: "KE",
    },
    geo: {
      "@type": "GeoCoordinates",
      latitude: -1.222539,
      longitude: 36.710752,
    },
    hasMap: CONTACT.mapsUrl,
    sameAs: [SOCIAL.facebook, SOCIAL.instagram, SOCIAL.tiktok],
    areaServed: [
      "Wangige",
      "Lower Kabete",
      "Gathiga",
      "Kibichiku",
      "Gikuni",
      "Kirangari",
      "Kingeero",
      "Kabete",
      "Ndenderu",
      "Kitisuru",
      "Kiambu County",
      "Nairobi",
    ],
  };
}

export function websiteJsonLd() {
  return {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "@id": `${SITE_URL}/#website`,
    url: `${SITE_URL}/`,
    name: SITE_NAME,
    publisher: { "@id": `${SITE_URL}/#school` },
    inLanguage: "en-KE",
  };
}

export function breadcrumbJsonLd(items: Array<{ name: string; path: string }>) {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: items.map((item, index) => ({
      "@type": "ListItem",
      position: index + 1,
      name: item.name,
      item: absoluteUrl(item.path),
    })),
  };
}

export function faqJsonLd(faqs: Array<{ question: string; answer: string }>) {
  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: faqs.map((faq) => ({
      "@type": "Question",
      name: faq.question,
      acceptedAnswer: {
        "@type": "Answer",
        text: faq.answer,
      },
    })),
  };
}
