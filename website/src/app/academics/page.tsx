import { RichPage, PageHero, SectionBlock, CtaBanner } from "@/components/layout/RichPage";
import { ACADEMICS_CONTENT, LEGACY_IMAGES } from "@/content/schoolContent";

const GROUPS = [
  { id: "early-years", title: "Early Years", stages: ACADEMICS_CONTENT.stages.slice(0, 2) },
  { id: "primary", title: "Primary School", stages: ACADEMICS_CONTENT.stages.slice(2, 4) },
  { id: "junior-secondary", title: "Junior Secondary", stages: ACADEMICS_CONTENT.stages.slice(4) },
];

export default function AcademicsPage() {
  return (
    <RichPage>
      <PageHero title="Academics" subtitle={ACADEMICS_CONTENT.intro} image={LEGACY_IMAGES.classroom} />

      {GROUPS.map((group, gi) => (
        <SectionBlock key={group.id} id={group.id} title={group.title} alt={gi % 2 === 1}>
          <div className="space-y-6">
            {group.stages.map((stage, i) => (
              <article
                key={stage.name}
                className="overflow-hidden rounded-rk-xl border border-rk-border bg-rk-white shadow-rk-soft"
              >
                <div className="flex flex-col lg:flex-row">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={stage.image} alt={stage.name} className="h-48 w-full object-cover lg:h-auto lg:w-72" />
                  <div className="flex flex-1 gap-4 p-5 sm:p-6">
                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rk-purple text-lg font-bold text-white">
                      {i + 1}
                    </div>
                    <div className="min-w-0 flex-1">
                      <h3 className="font-serif text-lg font-bold text-rk-deep-purple sm:text-xl">{stage.name}</h3>
                      <p className="text-sm font-medium text-rk-gold">Ages {stage.ages}</p>
                      <p className="rk-body-sm mt-2">{stage.focus}</p>
                    </div>
                  </div>
                </div>
              </article>
            ))}
          </div>
        </SectionBlock>
      ))}

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
            <div key={item} className="flex gap-3 rounded-rk-xl bg-rk-white p-4 ring-1 ring-rk-border">
              <span className="text-xl text-rk-gold">✓</span>
              <p className="rk-body-sm">{item}</p>
            </div>
          ))}
        </div>
      </SectionBlock>

      <SectionBlock>
        <CtaBanner
          title="Ready to Begin?"
          body="Discover how Royal Kings Premier School prepares learners for success — from first steps in Creche to confident graduates in Grade 9."
          href="/admissions"
          label="Apply for Admission"
        />
      </SectionBlock>
    </RichPage>
  );
}
