import type { BrandContent } from "@/types/brand";
import { CO_CURRICULAR, LEGACY_IMAGES, PILLARS } from "@/content/schoolContent";

const IMG = LEGACY_IMAGES;

export const BRAND_DEFAULTS: BrandContent = {
  trust_pill: [
    { title: "Since 2006", sort_order: 0 },
    { title: "CBC Aligned", sort_order: 1 },
    { title: "Safe Transport", sort_order: 2 },
    { title: "Christian Values", sort_order: 3 },
    { title: "Rich Co-Curricular", sort_order: 4 },
  ],
  school_card: [
    {
      title: "Creche & Early Years",
      subtitle: "Ages 3–5",
      body: "Strong beginnings through play, care, and foundational learning.",
      image_url: IMG.family2,
      link_url: "/academics",
      sort_order: 0,
    },
    {
      title: "Primary School",
      subtitle: "Grades 1–6",
      body: "Academic growth, creativity, and discovery.",
      image_url: IMG.classroom,
      link_url: "/academics",
      sort_order: 1,
    },
    {
      title: "Junior Secondary",
      subtitle: "Grades 7–9",
      body: "Leadership, discipline, and future readiness.",
      image_url: IMG.students,
      link_url: "/academics",
      sort_order: 2,
    },
  ],
  journey_milestone: [
    { title: "First Words", subtitle: "Age 3", body: "Joyful beginnings in our creche.", image_url: IMG.campus, sort_order: 0 },
    { title: "First Reading", subtitle: "Age 5–6", body: "Phonics and school readiness.", image_url: IMG.classroom, sort_order: 1 },
    { title: "First Performance", subtitle: "Age 7–9", body: "Music, drama, and confidence.", image_url: IMG.students, sort_order: 2 },
    { title: "First Competition", subtitle: "Age 10–12", body: "Sports, STEM, and talent showcases.", image_url: IMG.campus, sort_order: 3 },
    { title: "First Leadership", subtitle: "Age 13–15", body: "Prefects, mentors, and role models.", image_url: IMG.students, sort_order: 4 },
    { title: "Graduation Ready", subtitle: "Grade 9", body: "Prepared for the next chapter.", image_url: IMG.admissions, sort_order: 5 },
  ],
  cocurricular: CO_CURRICULAR.programs.map((p, i) => ({
    title: p.name,
    body: p.detail,
    image_url: IMG.students,
    settings: { icon: p.icon, size: i % 3 === 0 ? "large" : "medium" },
    sort_order: i,
  })),
  faith_pillar: [
    { title: "Faith", body: "Rooted in Christian values", sort_order: 0 },
    { title: "Family", body: "Partnership with parents", sort_order: 1 },
    { title: "Excellence", body: "Academic and character growth", sort_order: 2 },
  ],
  scripture: [
    {
      title: "Weekly Scripture",
      body: "Train up a child in the way he should go; even when he is old he will not depart from it. — Proverbs 22:6",
    },
  ],
  chaplain: [
    {
      title: "Chaplain's Message",
      body: "At Royal Kings Premier School, we nurture hearts as well as minds. Every child is known, loved, and guided in faith.",
    },
  ],
  leader: [
    { title: "School Director", subtitle: "Leadership", body: "Visionary leadership committed to excellence since 2006.", image_url: IMG.logo, sort_order: 0 },
    { title: "Head Teacher", subtitle: "Academics", body: "Dedicated to CBC excellence and whole-child development.", image_url: IMG.logo, sort_order: 1 },
    { title: "Chaplain", subtitle: "Spiritual Life", body: "Guiding learners in faith, character, and compassion.", image_url: IMG.logo, sort_order: 2 },
  ],
};

export const DISTINCTIVE_EDGE = PILLARS;
