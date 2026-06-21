import { RichPage, PageHero, SectionBlock } from "@/components/layout/RichPage";
import { CAMPUS_LIFE, LEGACY_IMAGES } from "@/content/schoolContent";

export default function CampusLifePage() {
  return (
    <RichPage>
      <PageHero title="Campus Life" subtitle={CAMPUS_LIFE.intro} image={LEGACY_IMAGES.campus} />
      <SectionBlock title="Life at Royal Kings">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {CAMPUS_LIFE.features.map((feature) => (
            <div key={feature} className="rounded-2xl bg-[var(--rk-surface)] p-5 text-sm text-[var(--rk-muted)] sm:p-6 sm:text-base">
              {feature}
            </div>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock alt>
        <div className="grid items-center gap-8 lg:grid-cols-2">
          <div>
            <h2 className="font-serif text-2xl font-bold text-[var(--rk-purple-dark)] sm:text-3xl">A Home Away From Home</h2>
            <p className="prose-rk mt-4">
              From morning assembly to afternoon sports, every moment at Royal Kings is designed to help children feel safe,
              loved, and inspired. Our Wangige campus blends modern learning spaces with warm, family-centered care.
            </p>
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={LEGACY_IMAGES.campus} alt="Royal Kings campus" className="w-full rounded-2xl object-cover shadow-lg" />
        </div>
      </SectionBlock>
    </RichPage>
  );
}
