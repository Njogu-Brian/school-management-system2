import { assetPath } from "@/lib/assetPath";

export function TransportHero() {
  return (
    <section className="relative overflow-hidden bg-rk-cream">
      <style>{`
        @keyframes rk-bus-arrive {
          from { transform: translate3d(-75vw, 10px, 0); }
          to { transform: translate3d(0, 0, 0); }
        }
        .rk-bus-arrive {
          animation: rk-bus-arrive 6.5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @media (prefers-reduced-motion: reduce) {
          .rk-bus-arrive { animation: none; }
        }
      `}</style>

      <div className="rk-container relative z-10 pb-8 pt-16 sm:pt-20 lg:pb-4 lg:pt-24">
        <p className="rk-overline text-rk-gold">School transport</p>
        <h1 className="mt-3 max-w-xl font-serif text-[clamp(2.4rem,5vw,4.25rem)] font-bold leading-[1.05] tracking-[-0.03em] text-rk-deep-purple">
          Every journey starts with care.
        </h1>
        <p className="mt-4 max-w-md text-lg text-rk-text">
          Safe, supervised transport across seven school routes.
        </p>
      </div>

      <div className="relative h-44 sm:h-56 lg:h-64" aria-hidden>
        <svg className="absolute inset-x-0 bottom-6 h-24 w-full sm:h-28" viewBox="0 0 1200 120" preserveAspectRatio="none">
          <path
            d="M0 78 C 180 78 220 28 420 36 C 640 44 700 96 920 70 C 1040 56 1120 48 1200 52"
            fill="none"
            stroke="#e4d3b0"
            strokeWidth="34"
            strokeLinecap="round"
          />
          <path
            d="M0 78 C 180 78 220 28 420 36 C 640 44 700 96 920 70 C 1040 56 1120 48 1200 52"
            fill="none"
            stroke="#d4af37"
            strokeWidth="2"
            strokeDasharray="8 14"
            strokeLinecap="round"
          />
        </svg>
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={assetPath("/images/school-bus-isuzu.png")}
          alt=""
          className="rk-bus-arrive absolute bottom-8 right-[6%] h-28 w-auto drop-shadow-xl sm:right-[14%] sm:h-40 lg:h-48"
        />
      </div>
    </section>
  );
}
