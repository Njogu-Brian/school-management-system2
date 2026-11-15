import type { Metadata } from "next";
import { ProgrammeLanding } from "@/components/seo/ProgrammeLanding";
import { CRECHE_LANDING, PRESCHOOL_LANDING } from "@/content/seoLandingContent";
import { LEGACY_IMAGES } from "@/content/schoolContent";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata("/creche-early-years");

export default function CrecheEarlyYearsPage() {
  return (
    <ProgrammeLanding
      path="/creche-early-years"
      h1="Creche and Early Years at Royal Kings in Wangige"
      subtitle="A gentle start to school — play, care, and readiness for Grade 1"
      heroImage={LEGACY_IMAGES.classroom}
      heroAlt="Early years learners at Royal Kings Premier School Wangige"
      intro={CRECHE_LANDING.intro}
      sections={[
        {
          title: "What Early Years includes",
          items: CRECHE_LANDING.points.map((point) => ({
            title: point,
            body: "Part of our Christian-centred early childhood programme in Riverside, Wangige.",
          })),
        },
        {
          title: "From Creche to PP2",
          items: PRESCHOOL_LANDING.levels,
          alt: true,
        },
      ]}
      faqs={PRESCHOOL_LANDING.faqs}
      related={[
        { href: "/preschool-wangige", label: "Preschool in Wangige" },
        { href: "/primary-school-wangige", label: "Primary School" },
        { href: "/admissions", label: "Admissions" },
        { href: "/contact-us", label: "Contact" },
      ]}
    />
  );
}
