import { RichPage, PageHero, SectionBlock, CtaBanner } from "@/components/layout/RichPage";
import { FEES_NOTE } from "@/content/schoolContent";

export default function FeesPage() {
  return (
    <RichPage>
      <PageHero title="School Fees" subtitle="Transparent, flexible, and designed for lasting value in Christian education." />
      <SectionBlock>
        <p className="mx-auto max-w-3xl text-center text-[var(--rk-muted)]">{FEES_NOTE.intro}</p>
        <ul className="mx-auto mt-8 grid max-w-3xl gap-3 sm:grid-cols-2">
          {FEES_NOTE.points.map((p) => (
            <li key={p} className="rounded-xl bg-[var(--rk-cream)] p-4 text-sm text-[var(--rk-muted)] ring-1 ring-[var(--rk-border)]">{p}</li>
          ))}
        </ul>
      </SectionBlock>
      <SectionBlock title="What Your Investment Covers" alt>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {["Tuition & CBC curriculum", "Co-curricular access", "Pastoral care & devotions", "School events", "Meals programme", "Transport (optional)"].map((item) => (
            <div key={item} className="rounded-xl bg-white p-5 text-center shadow-[var(--rk-shadow-soft)] ring-1 ring-[var(--rk-border)]">
              <span className="text-[var(--rk-gold)]">✓</span>
              <p className="mt-2 text-sm font-medium text-[var(--rk-purple-dark)]">{item}</p>
            </div>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock>
        <CtaBanner title="Request Current Fee Structure" body={FEES_NOTE.cta} href="/contact" label="Contact Admissions" />
      </SectionBlock>
    </RichPage>
  );
}
