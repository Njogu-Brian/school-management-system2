import { RichPage, PageHero, SectionBlock, CtaBanner } from "@/components/layout/RichPage";

const SAFEGUARDING = [
  { title: "Safeguarding Policy", detail: "Every staff member is committed to the safety and dignity of every learner." },
  { title: "Supervised Campus", detail: "Classrooms, playgrounds, and common areas are actively supervised throughout the day." },
  { title: "Secure Pick-up", detail: "Only authorised guardians may collect children. ID verification for new collectors." },
  { title: "Visitor Policy", detail: "All visitors sign in at the office and are escorted while on campus." },
  { title: "Transport Safety", detail: "Vetted drivers, seatbelts, and caring attendants on all routes." },
  { title: "Reporting", detail: "Parents can report concerns directly to leadership — we respond promptly." },
];

export default function ChildSafetyPage() {
  return (
    <RichPage>
      <PageHero title="Child Safety & Safeguarding" subtitle="Your child's wellbeing is our highest priority at Royal Kings Premier School." />
      <SectionBlock>
        <p className="mx-auto max-w-3xl text-center text-[var(--rk-muted)]">
          We maintain a warm, secure environment where children feel safe to learn, play, and grow. Our safeguarding practices meet the expectations of discerning parents across Wangige and surrounding communities.
        </p>
      </SectionBlock>
      <SectionBlock title="Our Commitment" alt>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {SAFEGUARDING.map((item) => (
            <article key={item.title} className="rounded-2xl bg-white p-6 shadow-[var(--rk-shadow-soft)] ring-1 ring-[var(--rk-border)]">
              <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">{item.title}</h3>
              <p className="mt-2 text-sm text-[var(--rk-muted)]">{item.detail}</p>
            </article>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock>
        <CtaBanner title="Questions About Safety?" body="Speak with our leadership team during your campus tour." href="/contact" label="Book a Visit" />
      </SectionBlock>
    </RichPage>
  );
}
