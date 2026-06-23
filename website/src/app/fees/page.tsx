import {
  RichPage,
  PageHero,
  SectionBlock,
  CtaBanner,
  InfoCardGrid,
  EditorialIntro,
} from "@/components/layout/RichPage";
import { FEES_NOTE, LEGACY_IMAGES } from "@/content/schoolContent";

const COVERS = [
  { title: "Tuition & CBC Curriculum", description: "Competency-based learning from Creche through Grade 9 with dedicated teachers.", icon: "📚" },
  { title: "Co-Curricular Access", description: "Skating, ballet, coding, music, sports, worship, and more included in school life.", icon: "⭐" },
  { title: "Pastoral Care", description: "Daily devotions, chaplain support, and Christian character formation.", icon: "✝️" },
  { title: "School Events", description: "Sports days, talent showcases, open days, and graduation ceremonies.", icon: "🎉" },
  { title: "Meals Programme", description: "Balanced, nutritious meals prepared by caring kitchen staff.", icon: "🍽️" },
  { title: "Transport (Optional)", description: "Safe routes serving Wangige, Lower Kabete, Kikuyu, Gitaru & Uthiru.", icon: "🚌" },
];

const FLEXIBILITY = [
  { title: "Transparent Structure", description: "Full fee breakdown shared during your campus tour or admissions consultation." },
  { title: "Flexible Payments", description: "Payment plans discussed with our finance office to suit your family." },
  { title: "Sibling Benefits", description: "Discounts may apply for multiple children — enquire with admissions." },
  { title: "Value for Investment", description: "Premium Christian education designed for lasting academic and character returns." },
];

export default function FeesPage() {
  return (
    <RichPage>
      <PageHero
        title="School Fees"
        subtitle="Transparent, flexible, and designed for lasting value in Christian education."
        image={LEGACY_IMAGES.classroom}
      />

      <SectionBlock>
        <EditorialIntro>{FEES_NOTE.intro}</EditorialIntro>
      </SectionBlock>

      <SectionBlock title="What Your Fees Cover" intro="Your investment supports the whole child — academically, spiritually, and socially." alt>
        <InfoCardGrid items={COVERS} />
      </SectionBlock>

      <SectionBlock title="Payment Flexibility">
        <InfoCardGrid items={FLEXIBILITY} />
      </SectionBlock>

      <SectionBlock alt>
        <CtaBanner
          title="Request Current Fee Structure"
          body={FEES_NOTE.cta}
          href="/contact"
          label="Contact Admissions"
        />
      </SectionBlock>
    </RichPage>
  );
}
