import {
  RichPage,
  PageHero,
  SectionBlock,
  CtaBanner,
  InfoCardGrid,
  EditorialIntro,
} from "@/components/layout/RichPage";
import { LEGACY_IMAGES } from "@/content/schoolContent";

const SAFEGUARDING = [
  { title: "Safeguarding Policy", description: "Every staff member is committed to the safety, dignity, and wellbeing of every learner." },
  { title: "Staff Training", description: "Regular safeguarding awareness and child protection protocols across the school." },
  { title: "Reporting Channels", description: "Parents can raise concerns directly with leadership — we respond promptly and confidentially." },
];

const SUPERVISION = [
  { title: "Supervised Campus", description: "Classrooms, playgrounds, and common areas are actively supervised throughout the day." },
  { title: "Safe Playgrounds", description: "Outdoor spaces designed for physical development with attentive oversight." },
  { title: "Transport Safety", description: "Vetted drivers, seatbelts, and caring attendants on all routes." },
];

const PICKUP = [
  { title: "Authorised Collection", description: "Only approved guardians may collect children. ID verification for new collectors." },
  { title: "Sign-out Procedures", description: "Clear handover protocols at end-of-day collection points." },
  { title: "Emergency Contacts", description: "Up-to-date contact records maintained for every learner." },
];

const VISITORS = [
  { title: "Visitor Sign-in", description: "All visitors register at the office before entering campus." },
  { title: "Escorted Access", description: "Guests are accompanied while on school grounds." },
  { title: "Clear Boundaries", description: "Defined zones ensure learners remain safe and undisturbed." },
];

export default function ChildSafetyPage() {
  return (
    <RichPage>
      <PageHero
        title="Child Safety & Safeguarding"
        subtitle="Your child's wellbeing is our highest priority at Royal Kings Premier School."
        image={LEGACY_IMAGES.campus}
      />

      <SectionBlock>
        <EditorialIntro>
          We maintain a warm, secure environment where children feel safe to learn, play, and grow. Our safeguarding practices meet the expectations of discerning parents across Wangige and surrounding communities.
        </EditorialIntro>
      </SectionBlock>

      <SectionBlock title="Safeguarding" alt>
        <InfoCardGrid items={SAFEGUARDING.map((i) => ({ ...i, icon: "✓" }))} />
      </SectionBlock>

      <SectionBlock title="Supervision">
        <InfoCardGrid items={SUPERVISION.map((i) => ({ ...i, icon: "👁️" }))} />
      </SectionBlock>

      <SectionBlock title="Pick-up Security" alt>
        <InfoCardGrid items={PICKUP.map((i) => ({ ...i, icon: "🔐" }))} />
      </SectionBlock>

      <SectionBlock title="Visitor Policy">
        <InfoCardGrid items={VISITORS.map((i) => ({ ...i, icon: "🚪" }))} />
      </SectionBlock>

      <SectionBlock alt>
        <CtaBanner
          title="Questions About Safety?"
          body="Speak with our leadership team during your campus tour."
          href="/contact"
          label="Book a Visit"
        />
      </SectionBlock>
    </RichPage>
  );
}
