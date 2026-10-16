import type { Metadata } from "next";
import { CmsPageView } from "@/components/cms/CmsPageView";
import {
  RichPage,
  PageHero,
  SectionBlock,
  CtaBanner,
  InfoCardGrid,
  EditorialIntro,
} from "@/components/layout/RichPage";
import { CONTACT, LEGACY_IMAGES } from "@/content/schoolContent";
import { assetPath } from "@/lib/assetPath";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata("/transport");

const ROUTES = [
  {
    name: "Lower Kabete",
    stops: ["Uon Campus", "Childrens Home", "Kanyongo", "Kmautiini", "Kingeero"],
    x: 78,
    y: 64,
  },
  {
    name: "Wangige",
    stops: ["Riverside", "Bluevally", "Kangora", "Muthumu", "Kiawanugu", "Mararo", "Whitehouse"],
    x: 58,
    y: 18,
  },
  {
    name: "Cuura",
    stops: ["Gatumumu", "Kahuho", "Kiangotho", "Gakinduri"],
    x: 22,
    y: 16,
  },
  {
    name: "Muthure",
    stops: ["Greenbelt", "Rukubi", "Kanyariri", "Gitaru", "Kwa Magu"],
    x: 20,
    y: 68,
  },
  {
    name: "Kibichiku",
    stops: ["Mwimuto", "Gathiga", "Kwa Kahawa", "Kwa Rhoda", "Njathaini"],
    x: 50,
    y: 82,
  },
  {
    name: "Karura",
    stops: ["Kibiku", "Kirangari", "Karura-Kanyungu", "Kamuremo", "Munyaka", "Kihara", "Ndederu", "Ruaka"],
    x: 84,
    y: 34,
  },
  {
    name: "Ngecha",
    stops: ["Ruku", "Kabocha", "Ngecha", "Nyathuna", "Gikuni", "Kagira", "Mukui"],
    x: 12,
    y: 42,
  },
];

const HUB = { x: 48, y: 44 };

const SAFETY = [
  { title: "Vetted Drivers", description: "Experienced drivers who know every child by name and route." },
  { title: "Caring Attendants", description: "Supervised boarding, seatbelts, and attentive care throughout the journey." },
  { title: "Secure Pick-up", description: "Only authorised guardians may collect learners from buses and vans." },
  { title: "Route Monitoring", description: "Leadership oversight ensures standards are maintained daily." },
];

const PUNCTUALITY = [
  { title: "Morning Routes", description: "Reliable arrival before assembly — so learning starts on time." },
  { title: "Afternoon Return", description: "Predictable drop-off times parents can plan around." },
  { title: "Parent Communication", description: "Updates when routes or timings change — we keep you informed." },
];

export default function TransportPage() {
  return <CmsPageView slug="transport" fallback={<TransportContent />} />;
}

function TransportContent() {
  return (
    <RichPage>
      <PageHero
        title="School Transport"
        subtitle="School buses and vans on seven routes around Riverside Wangige — supervised, punctual, and easy to plan around."
        image={LEGACY_IMAGES.students}
      />

      <SectionBlock>
        <EditorialIntro>
          Our transport service is an extension of our care. Learners travel on school buses and vans, with attendants on board, so the day starts and ends calmly.
        </EditorialIntro>
      </SectionBlock>

      <SectionBlock title="Our Fleet" intro="Yellow school buses for the longer corridors, and vans for closer drop-offs. Enquire to confirm which vehicle serves your stop.">
        <Fleet />
      </SectionBlock>

      <SectionBlock
        title="Routes We Serve"
        intro="A schematic of the seven corridors from Royal Kings at Riverside Wangige. Drop-off points are listed with each route. This drawing is not a GPS map."
        alt
      >
        <RouteMap />
        <ul className="mt-10 grid gap-4 lg:grid-cols-2">
          {ROUTES.map((route) => (
            <li key={route.name} className="rounded-2xl border border-[var(--rk-border)] bg-white p-5">
              <p className="font-serif text-lg font-bold text-[var(--rk-purple-dark)]">
                {route.name}{" "}
                <span className="font-sans text-base font-medium text-rk-muted">
                  ({route.stops.join(", ")})
                </span>
              </p>
            </li>
          ))}
        </ul>
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
          body="WhatsApp or call us to confirm the stop nearest you, and whether a bus or a van covers it."
          href={`https://wa.me/${CONTACT.whatsapp}`}
          label="WhatsApp Us"
        />
      </SectionBlock>
    </RichPage>
  );
}

function Fleet() {
  return (
    <div className="grid items-end gap-6 md:grid-cols-3">
      <FleetCard
        src={assetPath("/images/school-bus-isuzu.png")}
        alt="Royal Kings Isuzu school bus"
        label="School bus"
      />
      <FleetCard
        src={assetPath("/images/school-bus-coach.png")}
        alt="Royal Kings coach"
        label="Coach"
      />
      <figure className="flex flex-col items-center rounded-3xl bg-rk-cream px-6 py-8">
        <VanIcon />
        <figcaption className="mt-4 font-semibold text-[var(--rk-purple-dark)]">School van</figcaption>
      </figure>
    </div>
  );
}

function FleetCard({ src, alt, label }: { src: string; alt: string; label: string }) {
  return (
    <figure className="flex flex-col items-center rounded-3xl bg-rk-cream px-4 py-6">
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img src={src} alt={alt} className="h-40 w-full object-contain" />
      <figcaption className="mt-3 font-semibold text-[var(--rk-purple-dark)]">{label}</figcaption>
    </figure>
  );
}

function VanIcon() {
  return (
    <svg viewBox="0 0 220 120" className="h-40 w-full" role="img" aria-label="School van">
      <rect x="18" y="38" width="150" height="48" rx="8" fill="#f5c518" />
      <path d="M168 50h28l16 18v18h-44V50z" fill="#f5c518" />
      <rect x="176" y="58" width="22" height="16" rx="2" fill="#1c2830" />
      <rect x="36" y="48" width="28" height="18" rx="2" fill="#1c2830" />
      <rect x="72" y="48" width="28" height="18" rx="2" fill="#1c2830" />
      <rect x="108" y="48" width="28" height="18" rx="2" fill="#1c2830" />
      <circle cx="62" cy="90" r="12" fill="#222" />
      <circle cx="62" cy="90" r="5" fill="#ddd" />
      <circle cx="168" cy="90" r="12" fill="#222" />
      <circle cx="168" cy="90" r="5" fill="#ddd" />
    </svg>
  );
}

function RouteMap() {
  return (
    <div className="relative mx-auto aspect-[5/4] w-full max-w-4xl overflow-hidden rounded-3xl border border-[var(--rk-border)] bg-[#f4efe6]">
      <svg viewBox="0 0 100 80" className="absolute inset-0 h-full w-full" aria-hidden>
        {ROUTES.map((route) => (
          <line
            key={route.name}
            x1={HUB.x}
            y1={HUB.y}
            x2={route.x}
            y2={route.y}
            stroke="#c4b39a"
            strokeWidth="0.7"
            strokeLinecap="round"
          />
        ))}
        {ROUTES.flatMap((route) =>
          route.stops.map((_, index) => {
            const t = (index + 1) / (route.stops.length + 1);
            return (
              <circle
                key={`${route.name}-${index}`}
                cx={HUB.x + (route.x - HUB.x) * t}
                cy={HUB.y + (route.y - HUB.y) * t}
                r="0.9"
                fill="#4a0072"
              />
            );
          }),
        )}
        <circle cx={HUB.x} cy={HUB.y} r="2.4" fill="#e8b923" stroke="#4a0072" strokeWidth="0.4" />
      </svg>

      <p
        className="absolute max-w-[9rem] text-center text-[11px] font-bold leading-tight text-[var(--rk-purple-dark)] sm:text-xs"
        style={{ left: `${HUB.x}%`, top: `${HUB.y}%`, transform: "translate(-50%, -130%)" }}
      >
        Royal Kings
        <span className="block font-medium">Riverside Wangige</span>
      </p>

      {ROUTES.map((route) => (
        <p
          key={route.name}
          className="absolute whitespace-nowrap rounded-full bg-white/90 px-2 py-1 text-[11px] font-semibold text-[var(--rk-purple-dark)] shadow-sm sm:text-xs"
          style={{ left: `${route.x}%`, top: `${route.y}%`, transform: "translate(-50%, -50%)" }}
        >
          {route.name}
        </p>
      ))}
    </div>
  );
}
