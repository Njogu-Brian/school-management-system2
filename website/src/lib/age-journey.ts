import type { AgeJourneyStep } from "@/types/website";
import { PILLARS } from "@/content/schoolContent";

export const AGE_JOURNEY: AgeJourneyStep[] = [
  { age: 3, level: "Creche", classroom: "Baby Class", activities: ["Play-based learning", "Bible stories", "Motor skills"], milestones: ["First friendships", "Routine & independence"] },
  { age: 4, level: "Creche", classroom: "Middle Class", activities: ["Phonics introduction", "Creative arts", "Outdoor play"], milestones: ["Letter recognition", "Sharing & caring"] },
  { age: 5, level: "Creche", classroom: "Top Class", activities: ["Pre-reading", "Number sense", "Music & movement"], milestones: ["School readiness", "Confidence building"] },
  { age: 6, level: "Foundation", classroom: "Foundation", activities: ["Literacy foundations", "Bible club", "Swimming intro"], milestones: ["Primary transition prep"] },
  { age: 7, level: "PP1", classroom: "PP1", activities: ["CBC competencies", "Coding basics", "Sports"], milestones: ["Independent reading begins"] },
  { age: 8, level: "PP2", classroom: "PP2", activities: ["STEM projects", "Worship team", "Ballet/Skating"], milestones: ["Critical thinking growth"] },
  { age: 9, level: "Grade 1", classroom: "Grade 1", activities: ["Core subjects", "Archery", "Music"], milestones: ["Academic routines established"] },
  { age: 10, level: "Grade 2", classroom: "Grade 2", activities: ["Science labs", "Debating", "Sports teams"], milestones: ["Leadership opportunities"] },
  { age: 11, level: "Grade 3", classroom: "Grade 3", activities: ["Research projects", "Coding club", "Arts showcase"], milestones: ["Competency mastery"] },
  { age: 12, level: "Grade 4", classroom: "Grade 4", activities: ["STEM fair", "Music ensemble", "Community service"], milestones: ["Character formation"] },
  { age: 13, level: "Grade 5", classroom: "Grade 5", activities: ["Advanced literacy", "Sports excellence", "Worship leadership"], milestones: ["Pre-teen mentorship"] },
  { age: 14, level: "Grade 6", classroom: "Grade 6", activities: ["Junior leadership", "Competitions", "Career awareness"], milestones: ["Junior high readiness"] },
  { age: 15, level: "Grade 9", classroom: "Grade 9", activities: ["Exam preparation", "Senior projects", "Co-curricular mastery"], milestones: ["Ready for next chapter"] },
];

export const LEARNING_PATHWAY = [
  "Creche",
  "Foundation",
  "PP1",
  "PP2",
  "Grade 1–6",
  "Grade 7–9",
];

export const PROGRAMS = [
  { name: "Coding", icon: "💻" },
  { name: "Music", icon: "🎵" },
  { name: "Ballet", icon: "🩰" },
  { name: "Skating", icon: "⛸️" },
  { name: "Archery", icon: "🏹" },
  { name: "Sports", icon: "⚽" },
  { name: "Worship", icon: "✝️" },
];

export const WHY_ROYAL_KINGS = PILLARS.map((p) => ({
  title: p.title,
  description: p.description,
  icon: p.icon,
}));
