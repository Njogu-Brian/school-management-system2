import { RichPage, PageHero, SectionBlock, CtaBanner, EditorialIntro } from "@/components/layout/RichPage";
import { LEGACY_IMAGES } from "@/content/schoolContent";

const FAQS = [
  {
    q: "What age groups do you accept?",
    a: "Royal Kings Premier School serves learners from age 3 (Creche) through Grade 9 (Junior Secondary).",
  },
  {
    q: "Is Royal Kings a Christian school?",
    a: "Yes. We are a Christian-centered school with daily devotions, chaplain support, and values-based education woven into every stage.",
  },
  {
    q: "How do I apply?",
    a: "Start with an enquiry or campus tour, then complete the online application at Admissions → Apply. Our team will guide you through assessment and enrolment.",
  },
  {
    q: "Do you offer transport?",
    a: "Yes. We serve Wangige, Lower Kabete, Kikuyu, Gitaru, Uthiru and surrounding areas. Contact admissions to confirm route availability.",
  },
  {
    q: "When are school fees due?",
    a: "Fee structures and payment plans are shared during your tour or admissions consultation. Flexible arrangements may be available.",
  },
  {
    q: "Can I visit the school before applying?",
    a: "Absolutely. We encourage families to book a visit — meet our teachers, tour the campus, and ask any questions.",
  },
];

export default function AdmissionsFaqPage() {
  return (
    <RichPage>
      <PageHero
        title="Admissions FAQ"
        subtitle="Answers to common questions from families exploring Royal Kings Premier School."
        image={LEGACY_IMAGES.admissions}
      />

      <SectionBlock>
        <EditorialIntro>
          Choosing a school is one of the most important decisions for your family. Here are answers to questions we hear most often — if you need more detail, our admissions team is happy to help.
        </EditorialIntro>
      </SectionBlock>

      <SectionBlock alt>
        <dl className="mx-auto max-w-3xl space-y-rk-8">
          {FAQS.map((item) => (
            <div key={item.q} className="border-b border-rk-border pb-rk-8 last:border-0">
              <dt className="font-serif text-lg font-bold text-rk-purple">{item.q}</dt>
              <dd className="rk-body-sm mt-rk-3">{item.a}</dd>
            </div>
          ))}
        </dl>
      </SectionBlock>

      <SectionBlock>
        <CtaBanner
          title="Still Have Questions?"
          body="WhatsApp or call our admissions team — we respond promptly."
          href="/contact"
          label="Contact Admissions"
        />
      </SectionBlock>
    </RichPage>
  );
}
