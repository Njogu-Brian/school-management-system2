"use client";

import { useEffect, useRef, useState } from "react";
import { routePath, TRANSPORT_HUB, TRANSPORT_ROUTES } from "@/content/transportRoutes";

export function RouteMap() {
  const [active, setActive] = useState(0);
  const [motionOk, setMotionOk] = useState(false);
  const buttons = useRef<Array<HTMLButtonElement | null>>([]);
  const route = TRANSPORT_ROUTES[active];

  useEffect(() => {
    setMotionOk(!window.matchMedia("(prefers-reduced-motion: reduce)").matches);
  }, []);

  function move(from: number, delta: number) {
    const count = TRANSPORT_ROUTES.length;
    const next = (from + delta + count) % count;
    setActive(next);
    buttons.current[next]?.focus();
  }

  return (
    <div className="grid items-start gap-8 lg:grid-cols-[minmax(0,1.4fr)_minmax(16rem,0.8fr)]">
      <div className="relative overflow-hidden rounded-rk-2xl border border-rk-border bg-[#f6f1e8]">
        <svg viewBox="0 0 800 560" className="h-auto w-full" role="img" aria-label="Stylized map of seven school routes from Royal Kings">
          <style>{`
            @keyframes rk-route-draw {
              from { stroke-dashoffset: 1; }
              to { stroke-dashoffset: 0; }
            }
            .rk-route-line {
              stroke-dasharray: 1;
              stroke-dashoffset: 1;
              animation: rk-route-draw 1.6s ease forwards;
            }
            @media (prefers-reduced-motion: reduce) {
              .rk-route-line { animation: none; stroke-dashoffset: 0; }
            }
          `}</style>

          {TRANSPORT_ROUTES.map((item, index) => {
            const selected = index === active;
            return (
              <path
                key={item.name}
                className="rk-route-line"
                d={routePath(item)}
                pathLength={1}
                fill="none"
                stroke={selected ? "#d4af37" : "#c4b39a"}
                strokeWidth={selected ? 7 : 3}
                strokeLinecap="round"
                style={{ animationDelay: `${index * 0.08}s` }}
              />
            );
          })}

          <circle cx={TRANSPORT_HUB.x} cy={TRANSPORT_HUB.y} r="16" fill="#4a0072" />
          <circle cx={TRANSPORT_HUB.x} cy={TRANSPORT_HUB.y} r="7" fill="#d4af37" />
          <text
            x={TRANSPORT_HUB.x}
            y={TRANSPORT_HUB.y - 28}
            textAnchor="middle"
            fill="#4a0072"
            fontSize="16"
            fontWeight="700"
          >
            Royal Kings
          </text>
          <text x={TRANSPORT_HUB.x} y={TRANSPORT_HUB.y - 12} textAnchor="middle" fill="#7a6b86" fontSize="12">
            Riverside Wangige
          </text>

          {motionOk ? (
            <circle key={route.name} r="9" fill="#f5c518" stroke="#4a0072" strokeWidth="2">
              <animateMotion dur="2.8s" fill="freeze" repeatCount="1" path={routePath(route)} />
            </circle>
          ) : (
            <circle cx={route.x} cy={route.y} r="9" fill="#f5c518" stroke="#4a0072" strokeWidth="2" />
          )}
        </svg>

        {TRANSPORT_ROUTES.map((item, index) => (
          <button
            key={item.name}
            type="button"
            ref={(node) => {
              buttons.current[index] = node;
            }}
            aria-pressed={index === active}
            className={`absolute -translate-x-1/2 -translate-y-1/2 rounded-full px-3 py-1.5 text-xs font-semibold shadow-sm transition sm:text-sm ${
              index === active
                ? "bg-rk-deep-purple text-white"
                : "bg-white text-rk-deep-purple hover:bg-rk-soft-lavender"
            }`}
            style={{ left: `${(item.x / 800) * 100}%`, top: `${(item.y / 560) * 100}%` }}
            onClick={() => setActive(index)}
            onKeyDown={(event) => {
              if (event.key === "ArrowRight" || event.key === "ArrowDown") {
                event.preventDefault();
                move(index, 1);
              }
              if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
                event.preventDefault();
                move(index, -1);
              }
            }}
          >
            {item.name}
          </button>
        ))}
      </div>

      <div aria-live="polite" className="rounded-rk-2xl border border-rk-border bg-white p-6 sm:p-8">
        <p className="rk-overline text-rk-gold">Route</p>
        <h3 className="mt-2 font-serif text-3xl font-bold text-rk-deep-purple">{route.name}</h3>
        <p className="mt-3 text-rk-text">Morning pick-up and afternoon drop-off.</p>
        <p className="mt-4 text-sm leading-relaxed text-rk-muted">{route.stops.join(" · ")}</p>
      </div>
    </div>
  );
}
