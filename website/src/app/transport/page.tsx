import { RichPage, PageHero, SectionBlock, CtaBanner } from "@/components/layout/RichPage";
import { CAMPUS_LIFE, CONTACT } from "@/content/schoolContent";

const ROUTES = ["Wangige", "Lower Kabete", "Kikuyu", "Gitaru", "Uthiru"];

export default function TransportPage() {
  return (
    <RichPage>
      <PageHero title="School Transport" subtitle="Safe, reliable, and stress-free commutes for Royal Kings families." />
      <SectionBlock title="Areas We Serve">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {ROUTES.map((area) => (
            <div key={area} className="rounded-2xl bg-[var(--rk-cream)] p-6 text-center ring-1 ring-[var(--rk-border)]">
              <span className="text-2xl">🚌</span>
              <p className="mt-2 font-serif text-lg font-bold text-[var(--rk-purple)]">{area}</p>
            </div>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock title="Safety & Care" alt>
        <div className="grid gap-4 sm:grid-cols-2">
          {CAMPUS_LIFE.features.filter((f) => f.title.includes("Transport") || f.title.includes("Safe") || f.title.includes("Play")).map((f) => (
            <article key={f.title} className="rounded-xl bg-white p-5 ring-1 ring-[var(--rk-border)]">
              <h3 className="font-semibold text-[var(--rk-purple)]">{f.title}</h3>
              <p className="mt-2 text-sm text-[var(--rk-muted)]">{f.detail}</p>
            </article>
          ))}
          {[
            { title: "Supervised Pick-up & Drop-off", detail: "Caring drivers and attendants know every child by name." },
            { title: "Punctuality", detail: "Reliable morning and afternoon routes for working parents." },
            { title: "Visitor Policy", detail: "Only authorised persons may collect learners." },
          ].map((f) => (
            <article key={f.title} className="rounded-xl bg-white p-5 ring-1 ring-[var(--rk-border)]">
              <h3 className="font-semibold text-[var(--rk-purple)]">{f.title}</h3>
              <p className="mt-2 text-sm text-[var(--rk-muted)]">{f.detail}</p>
            </article>
          ))}
        </div>
      </SectionBlock>
      <SectionBlock>
        <CtaBanner title="Enquire About Transport" body="WhatsApp or call us to confirm routes and availability for your area." href={`https://wa.me/${CONTACT.whatsapp}`} label="WhatsApp Us" />
      </SectionBlock>
    </RichPage>
  );
}
