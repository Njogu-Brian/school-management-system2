import type { Metadata } from "next";
import { ProgrammeLanding } from "@/components/seo/ProgrammeLanding";
import { JUNIOR_LANDING } from "@/content/seoLandingContent";
import { LEGACY_IMAGES } from "@/content/schoolContent";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata("/junior-school-wangige");

export default function JuniorSchoolWangigePage() {
  const c = JUNIOR_LANDING;
  return (
    <ProgrammeLanding
      path="/junior-school-wangige"
      h1="Junior School Education in Wangige – Grades 7 to 9"
      subtitle="CBC junior secondary at Royal Kings — leadership, STEM and talent development"
      heroImage={LEGACY_IMAGES.students}
      heroAlt="Junior school learners at Royal Kings Premier School Wangige"
      intro={c.intro}
      sections={[
        { title: "Grade 7, 8 and 9", items: c.grades },
        { title: "Junior School focus areas", items: c.pillars, alt: true },
        {
          title: "Admissions information",
          items: [
            {
              title: "Joining Junior School",
              body: "Families can apply for Grades 7–9 through Admissions. We welcome transfers and continuing Royal Kings primary graduates.",
            },
            {
              title: "Academic progression",
              body: "Junior School prepares learners for the next stage with mentoring, discipline, and a clear CBC pathway.",
            },
          ],
        },
      ]}
      faqs={c.faqs}
      related={[
        { href: "/primary-school-wangige", label: "Primary School" },
        { href: "/co-curricular-activities", label: "Co-curricular" },
        { href: "/admissions", label: "Admissions" },
        { href: "/contact-us", label: "Contact" },
      ]}
    />
  );
}
