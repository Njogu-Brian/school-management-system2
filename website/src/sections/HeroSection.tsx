"use client";

import Link from "next/link";
import type { WebsiteSettings, GalleryItem, PageSection } from "@/types/website";
import { BRAND } from "@/content/schoolContent";
import { LEGACY_HEROES } from "@/content/legacyGallery";
import { ResponsiveImage } from "@/components/media/ResponsiveImage";
import { mediaUrl } from "@/lib/premiumMedia";
import { sectionImage } from "@/lib/cmsSections";
import { EditableText } from "@/components/live-editor/EditableText";
import { EditableImage } from "@/components/live-editor/EditableImage";
import { useLiveEditorOptional } from "@/components/live-editor/LiveEditorProvider";

export function HeroSection({
  settings,
  heroMedia,
  cmsHero,
}: {
  settings?: WebsiteSettings;
  heroMedia?: GalleryItem;
  cmsHero?: PageSection;
}) {
  const editor = useLiveEditorOptional();
  const cmsImage = cmsHero ? sectionImage(cmsHero) : undefined;
  const defaultHeroImage =
    cmsImage || (heroMedia ? mediaUrl(heroMedia, "xl") ?? heroMedia.url : LEGACY_HEROES.homeMural);
  const heroImage = editor?.getImage("hero.image", defaultHeroImage) || defaultHeroImage;
  const heroVideoFallback = settings?.hero_video || "";
  const heroVideo = editor?.getVideo("hero.video", heroVideoFallback) || heroVideoFallback;
  const editing = Boolean(editor?.editing);

  const cmsTitle = cmsHero?.title?.trim() || "";
  const brandTitles = new Set(
    [BRAND.heroHeadline, BRAND.tagline, "Building a Sure Foundation"].filter(Boolean).map((t) => t.toLowerCase()),
  );
  const headline =
    cmsTitle && !brandTitles.has(cmsTitle.toLowerCase())
      ? cmsTitle
      : "Preschool, Primary and Junior School in Wangige, Kiambu County";
  const subline =
    cmsHero?.subtitle || BRAND.heroSubheadline || "Building a Sure Foundation – Where Learning is Fun!";
  const intro = cmsHero?.content?.replace(/\s+/g, " ").trim() || BRAND.heroIntro;
  const overline = `${BRAND.location} · Christian-Centered · Since ${BRAND.founded}`;

  return (
    <section className="relative flex min-h-[85vh] items-center justify-center overflow-hidden sm:min-h-[92vh]">
      <div className="absolute inset-0 z-0">
        {heroVideo && !editing ? (
          <video autoPlay muted loop playsInline className="absolute inset-0 h-full w-full object-cover">
            <source src={heroVideo} type="video/mp4" />
          </video>
        ) : editing ? (
          <div className="absolute inset-0">
            {heroVideo ? (
              <video
                autoPlay
                muted
                loop
                playsInline
                className="absolute inset-0 h-full w-full object-cover"
                src={heroVideo}
              />
            ) : (
              <EditableImage
                editKey="hero.image"
                src={heroImage}
                alt=""
                className="absolute inset-0 h-full w-full object-cover"
              />
            )}
            <div className="absolute bottom-6 left-1/2 z-[6] flex -translate-x-1/2 flex-wrap justify-center gap-2">
              <button
                type="button"
                className="rk-editable-image-badge"
                style={{ position: "static", transform: "none" }}
                onClick={() =>
                  editor?.openMediaPicker({
                    key: "hero.image",
                    kind: "image",
                    target: "image",
                    currentUrl: heroImage,
                    title: "Replace hero photo",
                  })
                }
              >
                Replace photo
              </button>
              <button
                type="button"
                className="rk-editable-image-badge"
                style={{ position: "static", transform: "none" }}
                onClick={() =>
                  editor?.openMediaPicker({
                    key: "hero.video",
                    kind: "video",
                    target: "video",
                    currentUrl: heroVideo,
                    title: heroVideo ? "Replace hero video" : "Add hero video",
                  })
                }
              >
                {heroVideo ? "Replace video" : "Add video"}
              </button>
            </div>
          </div>
        ) : (
          <ResponsiveImage
            src={heroImage}
            srcSet={heroMedia?.srcset}
            sizes="100vw"
            alt=""
            className="absolute inset-0 h-full w-full object-cover"
            priority
          />
        )}
      </div>

      <div className="absolute inset-0 z-[1]" style={{ background: "rgba(74, 0, 114, 0.42)" }} aria-hidden />

      <div className="rk-container relative z-10 py-rk-16 text-center sm:py-rk-20">
        <EditableText editKey="hero.overline" as="p" className="rk-overline mb-rk-4 text-[var(--rk-gold)]">
          {overline}
        </EditableText>

        <EditableText
          editKey="hero.headline"
          as="h1"
          className="mx-auto max-w-4xl font-serif text-[clamp(2.25rem,5.5vw,4.25rem)] font-bold leading-[1.08] tracking-[-0.02em] text-white"
        >
          {headline}
        </EditableText>

        {subline ? (
          <EditableText
            editKey="hero.subline"
            as="p"
            className="mx-auto mt-rk-4 max-w-3xl font-serif text-xl text-[var(--rk-gold)] sm:text-2xl"
          >
            {subline}
          </EditableText>
        ) : null}

        <EditableText
          editKey="hero.intro"
          as="p"
          multiline
          className="mx-auto mt-rk-6 max-w-2xl font-sans text-base leading-relaxed text-white/90 sm:text-lg sm:leading-[1.7]"
        >
          {intro}
        </EditableText>

        <div className="mt-rk-8 flex flex-wrap items-center justify-center gap-rk-3 sm:gap-rk-4">
          <Link href="/contact" className="rk-btn rk-btn-secondary-on-dark rk-btn-lg cursor-pointer">
            Book a Visit
          </Link>
        </div>
      </div>
    </section>
  );
}
