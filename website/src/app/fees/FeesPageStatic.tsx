import {
  RichPage,
  PageHero,
  SectionBlock,
  CtaBanner,
  InfoCardGrid,
  EditorialIntro,
} from "@/components/layout/RichPage";
import { FEES_NOTE } from "@/content/schoolContent";
import { LEGACY_HEROES } from "@/content/legacyGallery";

const COVERS = [
  { title: "Academic Tuition", description: "CBC-aligned learning from Creche through Grade 9 with dedicated, professional teachers.", icon: "📚" },
  { title: "Meals", description: "Balanced, nutritious meals prepared daily by our caring kitchen team.", icon: "🍽️" },
  { title: "Sports & Clubs", description: "Football, athletics, skating, ballet, coding, music, worship, and more.", icon: "⚽" },
  { title: "House Activities", description: "Inter-class competitions, sports days, and team-building events.", icon: "🏆" },
  { title: "Pastoral Care", description: "Daily devotions, chaplain support, and Christian character formation.", icon: "✝️" },
  { title: "Transport (Optional)", description: "Safe daily routes serving Wangige, Lower Kabete, Kikuyu, Gitaru & Uthiru.", icon: "🚌" },
];

export function FeesPageStatic() {
  const { payment } = FEES_NOTE;

  return (
    <RichPage>
      <PageHero
        title="School Fees"
        subtitle="Transparent value — tuition, meals, sports, and pastoral care in one investment."
        image={LEGACY_HEROES.fees}
      />

      <SectionBlock>
        <EditorialIntro>{FEES_NOTE.intro}</EditorialIntro>
        <p className="mx-auto mt-6 max-w-3xl text-center text-sm text-[var(--rk-muted)]">
          The following is included in school fees: {FEES_NOTE.included.join(" · ")}.
        </p>
      </SectionBlock>

      <SectionBlock title="What Your Fees Cover" intro="Your investment supports the whole child — academically, spiritually, and socially." alt>
        <InfoCardGrid items={COVERS} />
      </SectionBlock>

      <SectionBlock title="Accepted Payment Methods">
        <div className="mx-auto grid max-w-4xl gap-6 lg:grid-cols-2">
          <article className="rounded-2xl bg-white p-6 shadow-md ring-1 ring-[var(--rk-border)]">
            <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">Bank Transfer — Equity Bank</h3>
            <dl className="mt-4 space-y-2 text-sm text-[var(--rk-muted)]">
              <div><dt className="font-semibold text-[var(--rk-text)]">Branch</dt><dd>{payment.bank.branch}</dd></div>
              <div><dt className="font-semibold text-[var(--rk-text)]">Account Name</dt><dd>{payment.bank.accountName}</dd></div>
              <div><dt className="font-semibold text-[var(--rk-text)]">Account Number</dt><dd className="font-mono">{payment.bank.accountNumber}</dd></div>
              <div><dt className="font-semibold text-[var(--rk-text)]">SWIFT</dt><dd>{payment.bank.swift}</dd></div>
              <div><dt className="font-semibold text-[var(--rk-text)]">Bank & Branch Code</dt><dd>{payment.bank.bankCode}</dd></div>
            </dl>
          </article>
          <article className="rounded-2xl bg-white p-6 shadow-md ring-1 ring-[var(--rk-border)]">
            <h3 className="font-serif text-lg font-bold text-[var(--rk-purple)]">M-Pesa Paybill</h3>
            <p className="mt-2 text-sm text-[var(--rk-muted)]">
              Paybill <strong className="text-[var(--rk-text)]">{payment.mpesa.paybill}</strong> — Account: {payment.mpesa.accountHint}
            </p>
            <ol className="mt-4 list-decimal space-y-1 pl-5 text-sm text-[var(--rk-muted)]">
              {payment.mpesa.steps.map((step) => (
                <li key={step}>{step}</li>
              ))}
            </ol>
            <p className="mt-4 text-sm text-[var(--rk-muted)]">
              Equity Paybill <strong>{payment.equityPaybill.paybill}</strong> — Account: {payment.equityPaybill.accountHint}
            </p>
            <p className="mt-4 rounded-lg bg-[var(--rk-cream)] p-3 text-xs font-medium text-[var(--rk-purple-dark)]">{payment.notice}</p>
          </article>
        </div>
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
