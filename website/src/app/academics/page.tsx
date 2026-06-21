import { RichPage, PageHero, SectionBlock } from "@/components/layout/RichPage";
import { ACADEMICS_CONTENT } from "@/content/schoolContent";

export default function AcademicsPage() {
  return (
    <RichPage>
      <PageHero title="Academics" subtitle={ACADEMICS_CONTENT.intro} />
      <SectionBlock title="CBC Learning Pathway">
        <div className="space-y-4">
          {ACADEMICS_CONTENT.stages.map((stage, i) => (
            <article
              key={stage.name}
              className="flex flex-col gap-3 rounded-2xl border border-[var(--rk-border)] bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:gap-6 sm:p-6"
            >
              <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[var(--rk-purple)] text-lg font-bold text-white">
                {i + 1}
              </div>
              <div className="min-w-0 flex-1">
                <h3 className="font-serif text-lg font-bold text-[var(--rk-purple-dark)] sm:text-xl">{stage.name}</h3>
                <p className="text-sm font-medium text-[var(--rk-gold)]">Ages {stage.ages}</p>
                <p className="mt-1 text-sm text-[var(--rk-muted)] sm:text-base">{stage.focus}</p>
              </div>
            </article>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock title="Why Our Academics Stand Out" alt>
        <div className="grid gap-4 sm:grid-cols-2">
          {[
            "Competency-Based Curriculum (CBC) from Creche to Grade 9",
            "Dedicated, qualified teachers who know every child by name",
            "Continuous assessment focused on mastery, not cramming",
            "Christian values integrated into every learning experience",
            "STEM, literacy, and arts balanced for whole-child growth",
            "Clear progression from early years to junior secondary",
          ].map((item) => (
            <div key={item} className="flex gap-3 rounded-xl bg-white p-4 ring-1 ring-[var(--rk-border)]">
              <span className="text-[var(--rk-gold)]">✓</span>
              <p className="text-sm text-[var(--rk-muted)] sm:text-base">{item}</p>
            </div>
          ))}
        </div>
      </SectionBlock>
    </RichPage>
  );
}
