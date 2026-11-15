import type { Metadata } from "next";
import { ProgrammeLanding } from "@/components/seo/ProgrammeLanding";
import { PRIMARY_LANDING } from "@/content/seoLandingContent";
import { LEGACY_IMAGES } from "@/content/schoolContent";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata("/primary-school-wangige");

export default function PrimarySchoolWangigePage() {
  const c = PRIMARY_LANDING;
  return (
    <ProgrammeLanding
      path="/primary-school-wangige"
      h1="Primary School Education in Wangige – Grades 1 to 6"
      subtitle="CBC private primary school at Royal Kings Premier School, Riverside, Wangige"
      heroImage={LEGACY_IMAGES.classroom}
      heroAlt="Primary school learners at Royal Kings Premier School in Wangige"
      intro={c.intro}
      sections={[
        { title: "Grades 1 to 6", items: c.grades },
        { title: "How primary learners thrive", items: c.pillars, alt: true },
        {
          title: "Transport and admissions",
          items: [
            {
              title: "School transport",
              body: "Routes cover Wangige, Lower Kabete, Kabete, Kibichiku and neighbouring communities. Contact the school for the current map.",
            },
            {
              title: "Admissions information",
              body: "Apply online or visit campus. Our admissions team guides Grade 1 entry and transfers into other primary grades.",
            },
          ],
        },
      ]}
      faqs={c.faqs}
      related={[
        { href: "/preschool-wangige", label: "Preschool" },
        { href: "/junior-school-wangige", label: "Junior School" },
        { href: "/co-curricular-activities", label: "Co-curricular" },
        { href: "/admissions", label: "Admissions" },
        { href: "/school-fees", label: "Fees" },
      ]}
    />
  );
}
