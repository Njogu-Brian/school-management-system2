"use client";

import { use } from "react";
import Link from "next/link";
import { RichPage, PageHero, SectionBlock, PhotoGrid } from "@/components/layout/RichPage";
import {
  PP2_GRADUATION_GALLERY,
  PP2_GRADUATION_POST,
} from "@/content/pp2Graduation2026";
import { STORY_BY_SLUG } from "@/content/schoolStories2026";
import { assetPath } from "@/lib/assetPath";
import { useQuery } from "@tanstack/react-query";
import { websiteService } from "@/services/websiteService";

type StaticPost = {
  title: string;
  excerpt?: string;
  published_at?: string;
  featured_image?: string;
  bodyHtml: string;
  gallery?: { src: string; title: string; caption?: string }[];
};

const STATIC_POSTS: Record<string, StaticPost> = {
  ...Object.fromEntries(
    Object.entries(STORY_BY_SLUG).map(([slug, story]) => [
      slug,
      {
        title: story.title,
        excerpt: story.excerpt,
        published_at: story.published_at,
        featured_image: story.featured_image,
        bodyHtml: story.bodyHtml,
        gallery: story.gallery,
      },
    ])
  ),
  [PP2_GRADUATION_POST.slug]: {
    title: PP2_GRADUATION_POST.title,
    excerpt: PP2_GRADUATION_POST.excerpt,
    published_at: PP2_GRADUATION_POST.published_at,
    featured_image: PP2_GRADUATION_POST.featured_image,
    bodyHtml: PP2_GRADUATION_POST.bodyHtml,
    gallery: [...PP2_GRADUATION_GALLERY],
  },
  "2027-admissions-now-open": {
    title: "2027 Admissions Now Open",
    excerpt: "Enrol your child for preschool, primary, or junior secondary. Interviews run 4th–6th November 2026.",
    published_at: "2026",
    featured_image: assetPath("/images/2027-admissions-open.jpg"),
    bodyHtml: `
<p>Royal Kings Premier School is pleased to announce that <strong>2027 admissions and interviews</strong> are open.</p>
<p><strong>Interview dates:</strong> 4th–6th November 2026 · 9:00 AM<br/>
<strong>Location:</strong> Riverside, Wangige</p>
<p>We welcome families seeking quality CBC education from Creche through Grade 9 in a warm, Christian-centred community.</p>
<p><a href="/admissions/apply">Apply online</a> or WhatsApp 0719 396 233.</p>
`.trim(),
  },
};

export default function BlogPostPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = use(params);
  const { data, isLoading } = useQuery({
    queryKey: ["website", "blog", slug],
    queryFn: () => websiteService.getBlog(slug),
    retry: false,
  });

  const staticPost = STATIC_POSTS[slug];
  const title = staticPost?.title || data?.title || "Story";
  const excerpt = staticPost?.excerpt || data?.excerpt;
  const rawPublished = staticPost?.published_at || data?.published_at;
  const published = (() => {
    if (!rawPublished) return undefined;
    const d = new Date(rawPublished);
    if (Number.isNaN(d.getTime())) return rawPublished;
    return d.toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric" });
  })();
  const image = staticPost?.featured_image || data?.featured_image;
  const body = staticPost?.bodyHtml || data?.body;

  if (isLoading && !staticPost) {
    return (
      <RichPage>
        <PageHero title="Loading…" />
      </RichPage>
    );
  }

  if (!staticPost && !data) {
    return (
      <RichPage>
        <PageHero title="Story not found" subtitle="This post may have moved." />
        <SectionBlock>
          <Link href="/blog" className="font-semibold text-[var(--rk-purple)]">
            ← Back to News & Stories
          </Link>
        </SectionBlock>
      </RichPage>
    );
  }

  return (
    <RichPage>
      <PageHero title={title} subtitle={excerpt} image={image} />
      <SectionBlock>
        <p className="text-sm text-[var(--rk-muted)]">{published}</p>
        {body ? (
          <div
            className="prose prose-purple mt-6 max-w-none text-[var(--rk-muted)] [&_a]:text-[var(--rk-purple)] [&_ul]:list-disc [&_ul]:pl-5"
            dangerouslySetInnerHTML={{ __html: body }}
          />
        ) : null}

        <div className="mt-10 flex flex-wrap gap-3">
          <Link href="/blog" className="rounded-full border border-[var(--rk-purple)] px-5 py-2 text-sm font-semibold text-[var(--rk-purple)]">
            ← All stories
          </Link>
          <Link href="/gallery" className="rounded-full bg-[var(--rk-purple)] px-5 py-2 text-sm font-semibold text-white">
            View gallery
          </Link>
          <Link href="/admissions/apply" className="rounded-full border border-[var(--rk-gold,#c9a227)] px-5 py-2 text-sm font-semibold text-[var(--rk-purple-dark)]">
            2027 Admissions
          </Link>
        </div>
      </SectionBlock>

      {staticPost?.gallery?.length ? (
        <SectionBlock
          alt
          title="In pictures"
          intro={
            slug === "pp2-graduation-2026"
              ? "Certificates, presentations, staff, and the march into ceremony — PP2 Graduation 2026."
              : slug.includes("trip") || slug.includes("career")
                ? "Photos from this event at Royal Kings Premier School."
                : "Moments from Royal Kings Premier School."
          }
        >
          <PhotoGrid photos={staticPost.gallery} />
        </SectionBlock>
      ) : null}
    </RichPage>
  );
}
