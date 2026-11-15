import type { Metadata } from "next";
import { ProgrammeLanding } from "@/components/seo/ProgrammeLanding";
import { PRESCHOOL_LANDING } from "@/content/seoLandingContent";
import { LEGACY_IMAGES } from "@/content/schoolContent";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata("/preschool-wangige");

export default function PreschoolWangigePage() {
  const c = PRESCHOOL_LANDING;
  return (
    <ProgrammeLanding
      path="/preschool-wangige"
      h1="A Nurturing Preschool and Early Years Learning Environment in Wangige"
      subtitle="Creche, Foundation, PP1 and PP2 at Royal Kings Premier School — Riverside, Wangige"
      heroImage={LEGACY_IMAGES.classroom}
      heroAlt="Preschool learners at Royal Kings Premier School in Wangige"
      intro={c.intro}
      sections={[
        { title: "Our Early Years pathway", items: c.levels },
        { title: "How young children learn with us", items: c.pillars, alt: true },
        {
          title: "Admissions, transport and next steps",
          items: [
            {
              title: "Admission requirements",
              body: "Bring the child’s birth certificate, recent passport photos, and parent contacts. Our team confirms class placement by age and readiness.",
            },
            {
              title: "School transport",
              body: "Buses serve Wangige, Lower Kabete, Kibichiku, Karura, Ngecha and nearby areas. Ask admissions about preschool-friendly routes.",
            },
            {
              title: "Teachers and learning environment",
              body: "Caring early years teachers, supervised play spaces, and Christian values shape a safe start to school life.",
            },
            {
              title: "Co-curricular beginnings",
              body: "Music, movement, outdoor play and celebration days help preschoolers discover confidence beyond the classroom.",
            },
          ],
        },
      ]}
      faqs={c.faqs}
      related={[
        { href: "/creche-early-years", label: "Creche & Early Years" },
        { href: "/primary-school-wangige", label: "Primary School" },
        { href: "/admissions", label: "Admissions" },
        { href: "/school-fees", label: "School Fees" },
        { href: "/school-transport", label: "Transport" },
      ]}
    />
  );
}
