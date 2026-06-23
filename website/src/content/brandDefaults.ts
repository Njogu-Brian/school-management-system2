import type { BrandContent } from "@/types/brand";
import { LEGACY_IMAGES, PILLARS } from "@/content/schoolContent";

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
      subtitle: "Grade 1–6",
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
    { title: "First Words", subtitle: "Age 3", body: "Joyful beginnings in our creche — first friendships and first discoveries.", image_url: IMG.campus, sort_order: 0 },
    { title: "First Reading", subtitle: "Age 5–6", body: "Phonics, school readiness, and the magic of opening a book.", image_url: IMG.classroom, sort_order: 1 },
    { title: "First Performance", subtitle: "Age 7–9", body: "Music, drama, and confidence on stage.", image_url: IMG.students, sort_order: 2 },
    { title: "First Competition", subtitle: "Age 10–12", body: "Sports, STEM fairs, and talent showcases.", image_url: IMG.campus, sort_order: 3 },
    { title: "First Leadership Role", subtitle: "Age 13–15", body: "Prefects, mentors, and role models for younger learners.", image_url: IMG.students, sort_order: 4 },
    { title: "Graduation", subtitle: "Grade 9", body: "Prepared for the next chapter with faith, character, and excellence.", image_url: IMG.admissions, sort_order: 5 },
  ],
  cocurricular: [
    { title: "Skating", body: "Grace, balance, and confidence on wheels.", image_url: IMG.students, settings: { size: "large" }, sort_order: 0 },
    { title: "Ballet", body: "Discipline, poise, and artistic expression.", image_url: IMG.students, settings: { size: "medium" }, sort_order: 1 },
    { title: "Coding", body: "Digital literacy and problem-solving from early primary.", image_url: IMG.classroom, settings: { size: "large" }, sort_order: 2 },
    { title: "Robotics", body: "Hands-on STEM and creative engineering.", image_url: IMG.students, settings: { size: "medium" }, sort_order: 3 },
    { title: "Archery", body: "Focus, precision, and competitive excellence.", image_url: IMG.students, settings: { size: "medium" }, sort_order: 4 },
    { title: "Music", body: "Choir, instruments, and school worship teams.", image_url: IMG.students, settings: { size: "large" }, sort_order: 5 },
    { title: "Sports", body: "Football, athletics, and team spirit.", image_url: IMG.students, settings: { size: "medium" }, sort_order: 6 },
    { title: "Worship", body: "Morning devotions and spiritual formation.", image_url: IMG.campus, settings: { size: "medium" }, sort_order: 7 },
  ],
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
