import { RichPage, PageHero, SectionBlock, CardGrid } from "@/components/layout/RichPage";
import { BRAND, INTRO, MISSION, PILLARS, LEGACY_IMAGES, HIGHLIGHTS } from "@/content/schoolContent";

export default function AboutPage() {
  return (
    <RichPage>
      <PageHero title="About Royal Kings" subtitle={INTRO.empowering} image={LEGACY_IMAGES.campus} />
      <SectionBlock>
        <div className="prose-rk mx-auto max-w-3xl text-center">
          <p>{INTRO.legacy}</p>
          <p>{INTRO.holistic}</p>
        </div>
      </SectionBlock>
      <SectionBlock title={MISSION.title} alt>
        <p className="mx-auto max-w-3xl text-center text-base leading-relaxed text-[var(--rk-muted)] sm:text-lg">{MISSION.body}</p>
      </SectionBlock>
      <SectionBlock title="Empowering Minds, Shaping Futures">
        <CardGrid items={PILLARS} />
      </SectionBlock>
      <SectionBlock title="Highlights" alt>
        <div className="grid gap-4 sm:grid-cols-3">
          {HIGHLIGHTS.map((h) => (
            <article key={h.title} className="rounded-2xl bg-white p-5 text-center shadow-sm ring-1 ring-[var(--rk-border)] sm:p-6">
              <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">{h.title}</h3>
              <p className="mt-2 text-sm text-[var(--rk-muted)]">{h.subtitle}</p>
            </article>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock>
        <div className="rounded-3xl bg-gradient-to-r from-[var(--rk-purple)] to-[var(--rk-purple-dark)] p-8 text-center text-white sm:p-12">
          <h2 className="font-serif text-2xl font-bold sm:text-3xl">Unleash Your Potential Today</h2>
          <p className="mx-auto mt-3 max-w-xl text-white/85">Embrace education that goes beyond textbooks and unlocks your child&apos;s true capabilities.</p>
          <a href="/admissions" className="mt-6 inline-block rounded-full bg-[var(--rk-gold)] px-8 py-3 font-bold text-[var(--rk-purple-deep)]">
            Start Your Adventure
          </a>
        </div>
      </SectionBlock>
    </RichPage>
  );
}
