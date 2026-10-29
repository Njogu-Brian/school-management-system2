import type { Metadata } from "next";
import { RouteMap } from "@/components/transport/RouteMap";
import { TransportHero } from "@/components/transport/TransportHero";
import { RichPage, SectionBlock, CtaBanner, InfoCardGrid } from "@/components/layout/RichPage";
import { CONTACT } from "@/content/schoolContent";
import { TRANSPORT_ROUTES } from "@/content/transportRoutes";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata("/transport");

const CARE = [
  {
    title: "Caring attendants",
    description: "A known adult rides with the learners, from boarding until they are handed to an authorised guardian.",
    icon: "🛡️",
  },
  {
    title: "Routine checks",
    description: "Buses and vans are checked before the morning pick-up and again before the afternoon return.",
    icon: "🔎",
  },
  {
    title: "We keep you informed",
    description: "When a route or a time changes, parents hear from the school before the journey.",
    icon: "💬",
  },
];

export default function TransportPage() {
  return (
    <RichPage>
      <TransportHero />

      <SectionBlock
        id="routes"
        title="Where we travel"
        intro="Seven routes. One trusted journey. Choose an area to see where that vehicle stops."
      >
        <RouteMap />
        <ul className="mt-10 grid gap-3 sm:grid-cols-2">
          {TRANSPORT_ROUTES.map((route) => (
            <li key={route.name} className="rounded-2xl border border-rk-border bg-rk-cream px-5 py-4">
              <p className="font-serif text-lg font-bold text-rk-deep-purple">
                {route.name}{" "}
                <span className="font-sans text-base font-medium text-rk-muted">({route.stops.join(", ")})</span>
              </p>
            </li>
          ))}
        </ul>
      </SectionBlock>

      <SectionBlock title="Transport that moves with your family" alt>
        <InfoCardGrid items={CARE} />
      </SectionBlock>

      <SectionBlock>
        <CtaBanner
          title="Enquire about your route"
          body="Tell us where you live and we will confirm the morning pick-up and afternoon drop-off."
          href={`https://wa.me/${CONTACT.whatsapp}`}
          label="Enquire about your route"
        />
      </SectionBlock>
    </RichPage>
  );
}
