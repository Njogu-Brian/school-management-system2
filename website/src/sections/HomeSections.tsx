"use client";

import { motion } from "framer-motion";
import { Swiper, SwiperSlide } from "swiper/react";
import { Autoplay, Pagination } from "swiper/modules";
import "swiper/css";
import "swiper/css/pagination";
import type { Testimonial, GalleryItem, WebsiteEvent, HomepageData } from "@/types/website";
import { LEGACY_TESTIMONIALS } from "@/content/schoolContent";
import { fadeUp } from "@/animations/variants";
import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import gsap from "gsap";

export function TestimonialsCarousel({ testimonials }: { testimonials: Testimonial[] }) {
  const items: Testimonial[] =
    testimonials.length > 0
      ? testimonials
      : LEGACY_TESTIMONIALS.map((message, id) => ({
          id,
          name: "Parent",
          relationship: "Royal Kings Family",
          message,
          featured: true,
        }));

  if (!items.length) return null;

  return (
    <section className="bg-[#faf7ff] py-20">
      <div className="mx-auto max-w-6xl px-4 lg:px-8">
        <h2 className="text-center font-serif text-3xl font-bold text-[#2a1145]">What Parents Say</h2>
        <Swiper modules={[Autoplay, Pagination]} autoplay={{ delay: 5000 }} pagination={{ clickable: true }} className="mt-10 !pb-12">
          {items.map((t) => (
            <SwiperSlide key={t.id}>
              <blockquote className="mx-auto max-w-3xl rounded-3xl bg-white p-8 text-center shadow-lg">
                <p className="text-lg italic text-[#4a3a5c]">&ldquo;{t.message}&rdquo;</p>
                <footer className="mt-6 font-semibold text-[#5B2C8E]">{t.name}{t.relationship ? ` · ${t.relationship}` : ""}</footer>
              </blockquote>
            </SwiperSlide>
          ))}
        </Swiper>
      </div>
    </section>
  );
}

export function CampusGallery({ items }: { items: GalleryItem[] }) {
  const scrollRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!scrollRef.current || items.length < 2) return;
    const el = scrollRef.current;
    gsap.to(el, { scrollLeft: el.scrollWidth / 2, duration: 20, repeat: -1, yoyo: true, ease: "none" });
  }, [items]);

  if (!items.length) return null;

  return (
    <section className="py-20">
      <div className="mx-auto max-w-6xl px-4 lg:px-8">
        <h2 className="font-serif text-3xl font-bold text-[#2a1145]">Campus Life</h2>
        <div ref={scrollRef} className="mt-8 flex gap-4 overflow-x-auto pb-4 scrollbar-hide">
          {items.map((item) => (
            <motion.div key={item.id} whileHover={{ scale: 1.03 }} className="min-w-[280px] shrink-0 overflow-hidden rounded-2xl shadow-lg">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={item.url} alt={item.alt_text || item.title} className="h-56 w-full object-cover" loading="lazy" />
              <p className="bg-white p-3 text-sm font-medium text-[#5B2C8E]">{item.title}</p>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}

function EventCountdown({ date }: { date: string }) {
  const target = new Date(date).getTime();
  const [left, setLeft] = useState({ d: 0, h: 0, m: 0 });

  useEffect(() => {
    const tick = () => {
      const diff = Math.max(0, target - Date.now());
      setLeft({
        d: Math.floor(diff / 86400000),
        h: Math.floor((diff % 86400000) / 3600000),
        m: Math.floor((diff % 3600000) / 60000),
      });
    };
    tick();
    const id = setInterval(tick, 60000);
    return () => clearInterval(id);
  }, [target]);

  return <span className="text-xs text-[#D4AF37]">{left.d}d {left.h}h {left.m}m</span>;
}

export function LatestEvents({ events }: { events: WebsiteEvent[] }) {
  const upcoming = events.slice(0, 4);
  if (!upcoming.length) return null;

  return (
    <section className="bg-[#2a1145] py-20 text-white">
      <div className="mx-auto max-w-6xl px-4 lg:px-8">
        <div className="flex items-end justify-between">
          <h2 className="font-serif text-3xl font-bold">Latest Events</h2>
          <Link href="/events" className="text-sm text-[#D4AF37] hover:underline">View all</Link>
        </div>
        <div className="mt-10 grid gap-6 md:grid-cols-2">
          {upcoming.map((event) => (
            <motion.article key={`${event.source}-${event.id}`} variants={fadeUp} initial="hidden" whileInView="visible" viewport={{ once: true }} className="overflow-hidden rounded-2xl bg-white/10 backdrop-blur">
              {event.cover_image && (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={event.cover_image} alt={event.title} className="h-40 w-full object-cover" loading="lazy" />
              )}
              <div className="p-5">
                <div className="flex items-center justify-between gap-2">
                  <h3 className="font-semibold">{event.title}</h3>
                  <EventCountdown date={event.start_date} />
                </div>
                <p className="mt-2 text-sm text-white/70">{event.location} · {event.start_date}</p>
              </div>
            </motion.article>
          ))}
        </div>
      </div>
    </section>
  );
}

export function StatsCounters({ stats }: { stats: HomepageData["live_stats"] }) {
  const [count, setCount] = useState(0);

  useEffect(() => {
    const target = stats.total_learners;
    let current = 0;
    const step = Math.max(1, Math.floor(target / 60));
    const id = setInterval(() => {
      current += step;
      if (current >= target) {
        setCount(target);
        clearInterval(id);
      } else setCount(current);
    }, 30);
    return () => clearInterval(id);
  }, [stats.total_learners]);

  return (
    <section className="py-16">
      <div className="mx-auto flex max-w-4xl flex-wrap justify-center gap-10 px-4 text-center">
        <div>
          <p className="font-serif text-5xl font-bold text-[#5B2C8E]">{count}+</p>
          <p className="mt-2 text-[#4a3a5c]">Happy Learners</p>
        </div>
        <div>
          <p className="font-serif text-5xl font-bold text-[#5B2C8E]">{stats.class_structure.length}</p>
          <p className="mt-2 text-[#4a3a5c]">Class Levels</p>
        </div>
        <div>
          <p className="font-serif text-5xl font-bold text-[#5B2C8E]">3–15</p>
          <p className="mt-2 text-[#4a3a5c]">Age Range</p>
        </div>
      </div>
    </section>
  );
}

export function AnnouncementsTicker({ announcements }: { announcements: HomepageData["announcements"] }) {
  if (!announcements.length) return null;

  return (
    <div className="overflow-hidden bg-[#D4AF37] py-2 text-[#2a1145]">
      <div className="animate-marquee whitespace-nowrap text-sm font-medium">
        {announcements.map((a) => (
          <span key={a.id} className="mx-8 inline-block">📢 {a.title}: {a.content.replace(/<[^>]+>/g, "").slice(0, 120)}</span>
        ))}
      </div>
    </div>
  );
}

export function TransportPreview() {
  const routes = ["Route A · Westlands", "Route B · Parklands", "Route C · Lavington", "Route D · Kilimani"];

  return (
    <section className="py-20">
      <div className="mx-auto max-w-6xl px-4 lg:px-8">
        <h2 className="font-serif text-3xl font-bold text-[#2a1145]">Safe Transport Routes</h2>
        <p className="mt-3 max-w-2xl text-[#4a3a5c]">GPS-tracked buses with caring drivers — tap a route to preview (full map in Parent Portal).</p>
        <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {routes.map((route) => (
            <button key={route} type="button" className="rounded-2xl border-2 border-[#e8dff5] bg-white p-5 text-left transition hover:border-[#5B2C8E] hover:shadow-lg">
              <span className="text-2xl">🚌</span>
              <p className="mt-3 font-semibold text-[#5B2C8E]">{route}</p>
            </button>
          ))}
        </div>
      </div>
    </section>
  );
}

export function ParentPortalPreview() {
  return (
    <section className="bg-gradient-to-r from-[#5B2C8E] to-[#3d1d61] py-20 text-white">
      <div className="mx-auto flex max-w-6xl flex-col items-center gap-8 px-4 text-center lg:flex-row lg:text-left lg:px-8">
        <div className="flex-1">
          <h2 className="font-serif text-3xl font-bold">Parent Portal</h2>
          <p className="mt-4 text-white/85">Fees, attendance, report cards, transport, and school announcements — all in one secure place.</p>
          <Link href="/parent-portal" className="mt-6 inline-block rounded-full bg-[#D4AF37] px-6 py-3 font-semibold text-[#2a1145]">
            Access Portal
          </Link>
        </div>
        <div className="flex-1 rounded-3xl bg-white/10 p-8 backdrop-blur">
          <div className="grid grid-cols-2 gap-4 text-sm">
            {["Fee Statements", "Attendance", "Report Cards", "Announcements"].map((f) => (
              <div key={f} className="rounded-xl bg-white/10 p-4">{f}</div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

export function AdmissionsCTA() {
  return (
    <section className="py-20">
      <div className="mx-auto max-w-4xl rounded-3xl bg-[#faf7ff] px-8 py-16 text-center shadow-xl">
        <h2 className="font-serif text-3xl font-bold text-[#2a1145]">Begin Your Journey Today</h2>
        <p className="mx-auto mt-4 max-w-xl text-[#4a3a5c]">Join a community where faith, excellence, and warmth guide every step from Creche to Grade 9.</p>
        <Link href="/admissions" className="mt-6 inline-block rounded-full bg-[#5B2C8E] px-6 py-3 font-semibold text-white hover:bg-[#4a2475]">
            Apply for Admission
          </Link>
          <Link href="/admissions/apply" className="ml-3 mt-6 inline-block rounded-full border border-[#5B2C8E] px-6 py-3 font-semibold text-[#5B2C8E]">
            Full Application
          </Link>
      </div>
    </section>
  );
}
