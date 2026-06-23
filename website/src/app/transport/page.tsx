import {
  RichPage,
  PageHero,
  SectionBlock,
  CtaBanner,
  InfoCardGrid,
  EditorialIntro,
} from "@/components/layout/RichPage";
import { CONTACT, LEGACY_IMAGES } from "@/content/schoolContent";

const ROUTES = [
  { title: "Wangige", description: "Door-to-door routes across our home community." },
  { title: "Lower Kabete", description: "Morning and afternoon services for working families." },
  { title: "Kikuyu", description: "Reliable pick-up and drop-off with caring attendants." },
  { title: "Gitaru", description: "Safe, supervised commutes every school day." },
  { title: "Uthiru", description: "Convenient routes — enquire for current availability." },
];

const SAFETY = [
  { title: "Vetted Drivers", description: "Experienced drivers who know every child by name and route." },
  { title: "Caring Attendants", description: "Supervised boarding, seatbelts, and attentive care throughout the journey." },
  { title: "Secure Pick-up", description: "Only authorised guardians may collect learners from buses." },
  { title: "Route Monitoring", description: "Leadership oversight ensures standards are maintained daily." },
];

const PUNCTUALITY = [
  { title: "Morning Routes", description: "Reliable arrival before assembly — so learning starts on time." },
  { title: "Afternoon Return", description: "Predictable drop-off times parents can plan around." },
  { title: "Parent Communication", description: "Updates when routes or timings change — we keep you informed." },
];

export default function TransportPage() {
  return (
    <RichPage>
      <PageHero
        title="School Transport"
        subtitle="Safe, reliable, and stress-free commutes for Royal Kings families across Kiambu."
        image={LEGACY_IMAGES.students}
      />

      <SectionBlock>
        <EditorialIntro>
          Our transport service is an extension of our care — supervised routes that give parents peace of mind and learners a calm start and end to every school day.
        </EditorialIntro>
      </SectionBlock>

      <SectionBlock title="Routes We Serve" intro="Enquire with admissions to confirm availability for your area." alt>
        <InfoCardGrid items={ROUTES.map((r) => ({ ...r, icon: "📍" }))} />
      </SectionBlock>

      <SectionBlock title="Safety First">
        <InfoCardGrid items={SAFETY.map((r) => ({ ...r, icon: "🛡️" }))} />
      </SectionBlock>

      <SectionBlock title="Punctuality You Can Trust" alt>
        <InfoCardGrid items={PUNCTUALITY.map((r) => ({ ...r, icon: "⏱️" }))} />
      </SectionBlock>

      <SectionBlock>
        <CtaBanner
          title="Enquire About Transport"
          body="WhatsApp or call us to confirm routes and availability for your area."
          href={`https://wa.me/${CONTACT.whatsapp}`}
          label="WhatsApp Us"
        />
      </SectionBlock>
    </RichPage>
  );
}
