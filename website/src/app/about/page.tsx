import { RichPage, PageHero, SectionBlock, CardGrid, StatsRow, PhotoGrid, CtaBanner } from "@/components/layout/RichPage";
import { BRAND, INTRO, MISSION, VISION, PILLARS, LEGACY_IMAGES, HIGHLIGHTS, STATS } from "@/content/schoolContent";
import { SocialBarLight } from "@/components/layout/SocialBar";

export default function AboutPage() {
  return (
    <RichPage>
      <PageHero title="About Us" subtitle={INTRO.empowering} image={LEGACY_IMAGES.campus} />
      <SectionBlock>
        <StatsRow stats={STATS} />
      </SectionBlock>
      <SectionBlock title="Our Story">
        <div className="grid items-center gap-8 lg:grid-cols-2">
          <div className="prose-rk">
            <p>{INTRO.legacy}</p>
            <p>{INTRO.aboutFull}</p>
            <p>{INTRO.holistic}</p>
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={LEGACY_IMAGES.campus} alt="Royal Kings Premier School campus" className="w-full rounded-2xl object-cover shadow-xl ring-2 ring-[var(--rk-border)]" />
        </div>
      </SectionBlock>
      <SectionBlock title={MISSION.title} alt>
        <p className="mx-auto max-w-3xl text-center text-base leading-relaxed text-[var(--rk-muted)] sm:text-lg">{MISSION.body}</p>
      </SectionBlock>
      <SectionBlock title={VISION.title}>
        <p className="mx-auto max-w-3xl text-center text-base leading-relaxed text-[var(--rk-muted)] sm:text-lg">{VISION.body}</p>
      </SectionBlock>
      <SectionBlock title="What Makes Us Royal Kings" alt>
        <CardGrid items={PILLARS} />
      </SectionBlock>
      <SectionBlock title="Highlights">
        <div className="grid gap-6 sm:grid-cols-3">
          {HIGHLIGHTS.map((h) => (
            <article key={h.title} className="overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-[var(--rk-border)]">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={h.image} alt={h.title} className="aspect-video w-full object-cover" />
              <div className="p-5 text-center sm:p-6">
                <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">{h.title}</h3>
                <p className="mt-2 text-sm text-[var(--rk-muted)]">{h.subtitle}</p>
              </div>
            </article>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock alt>
        <div className="flex flex-col items-center gap-6 text-center">
          <p className="text-sm font-semibold uppercase tracking-widest text-[var(--rk-purple)]">Stay Connected</p>
          <SocialBarLight />
          <CtaBanner
            title="Unleash Your Potential Today"
            body="Embrace education that goes beyond textbooks and unlocks your child's true capabilities at Royal Kings Premier School."
            href="/admissions"
            label="Start Your Adventure"
          />
        </div>
      </SectionBlock>
    </RichPage>
  );
}
