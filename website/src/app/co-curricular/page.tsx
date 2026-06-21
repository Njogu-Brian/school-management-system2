import { RichPage, PageHero, SectionBlock } from "@/components/layout/RichPage";
import { CO_CURRICULAR } from "@/content/schoolContent";

export default function CoCurricularPage() {
  return (
    <RichPage>
      <PageHero title="Co-Curricular Programmes" subtitle={CO_CURRICULAR.intro} />
      <SectionBlock>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {CO_CURRICULAR.programs.map((p) => (
            <article key={p.name} className="rounded-2xl border border-[var(--rk-border)] bg-white p-5 shadow-sm sm:p-6">
              <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">{p.name}</h3>
              <p className="mt-2 text-sm text-[var(--rk-muted)] sm:text-base">{p.detail}</p>
            </article>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock title="November Talent Camp" alt>
        <p className="mx-auto max-w-2xl text-center text-[var(--rk-muted)]">
          Our annual November Talent Camp gives learners a holiday boost in creativity, performance, and confidence — a hallmark
          experience families look forward to every year at Royal Kings.
        </p>
      </SectionBlock>
    </RichPage>
  );
}
