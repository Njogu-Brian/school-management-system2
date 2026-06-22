import Link from "next/link";

export function AdmissionsBanner() {
  return (
    <section className="bg-gradient-to-r from-[var(--rk-gold-light)] via-[var(--rk-gold)] to-[var(--rk-gold-light)] py-12">
      <div className="mx-auto max-w-4xl px-4 text-center">
        <p className="text-xs font-bold uppercase tracking-[0.2em] text-[var(--rk-purple-deep)]">Admissions Ongoing</p>
        <h2 className="mt-2 font-serif text-2xl font-bold text-[var(--rk-purple-deep)] sm:text-3xl">Begin Your Child&apos;s Journey at Royal Kings Premier School</h2>
        <p className="mx-auto mt-3 max-w-xl text-sm text-[var(--rk-purple-dark)]/80">Limited spaces available for 2025. Tour our Wangige campus and meet our teachers.</p>
        <div className="mt-6 flex flex-wrap justify-center gap-3">
          <Link href="/admissions/apply" className="rounded-full bg-[var(--rk-purple)] px-8 py-3 text-sm font-bold text-white hover:bg-[var(--rk-purple-dark)]">
            Apply Now
          </Link>
          <Link href="/contact" className="rounded-full border-2 border-[var(--rk-purple-deep)] px-8 py-3 text-sm font-bold text-[var(--rk-purple-deep)] hover:bg-white/40">
            Book a Visit
          </Link>
        </div>
      </div>
    </section>
  );
}
