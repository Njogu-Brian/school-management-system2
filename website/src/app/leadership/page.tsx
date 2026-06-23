"use client";

import {
  RichPage,
  PageHero,
  SectionBlock,
  CtaBanner,
  LeaderCard,
  EditorialIntro,
} from "@/components/layout/RichPage";
import { useBrandContent } from "@/hooks/useBrandContent";
import { LEGACY_IMAGES } from "@/content/schoolContent";

const FALLBACK_LEADERS = [
  {
    title: "School Director",
    subtitle: "Leadership",
    body: "Visionary leadership committed to excellence since 2006 — guiding Royal Kings with faith and purpose.",
    image_url: LEGACY_IMAGES.logo,
  },
  {
    title: "Head Teacher",
    subtitle: "Academics",
    body: "Dedicated to CBC excellence, teacher development, and whole-child growth across every grade.",
    image_url: LEGACY_IMAGES.logo,
  },
  {
    title: "Chaplain",
    subtitle: "Spiritual Life",
    body: "Nurturing hearts and minds through worship, devotions, and pastoral care for every learner.",
    image_url: LEGACY_IMAGES.logo,
  },
];

export default function LeadershipPage() {
  const brand = useBrandContent();
  const leaders = brand.items("leader");
  const team = leaders.length > 0 ? leaders : FALLBACK_LEADERS;

  return (
    <RichPage>
      <PageHero
        title="Our Leadership"
        subtitle="Faces you can trust — guiding Royal Kings Premier School with faith, excellence, and care."
        image={LEGACY_IMAGES.campus}
      />

      <SectionBlock>
        <EditorialIntro>
          Our leadership team brings together decades of experience in Christian education. They know learners by name, partner with parents, and set the tone for a school that feels like family.
        </EditorialIntro>
      </SectionBlock>

      <SectionBlock title="Meet Our Team" alt>
        <div className="grid gap-rk-12 md:grid-cols-3">
          {team.map((leader) => (
            <LeaderCard
              key={leader.title}
              name={leader.title ?? ""}
              role={leader.subtitle}
              bio={leader.body}
              image={leader.image_url}
            />
          ))}
        </div>
      </SectionBlock>

      <SectionBlock>
        <CtaBanner
          title="Visit & Meet the Team"
          body="Book a campus tour and speak directly with our leadership during your visit."
          href="/contact"
          label="Book a Visit"
        />
      </SectionBlock>
    </RichPage>
  );
}
