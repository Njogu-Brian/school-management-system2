"use client";

import { RichPage, PageHero, SectionBlock } from "@/components/layout/RichPage";
import { INTRO } from "@/content/schoolContent";
import { useBlogs } from "@/hooks/useWebsiteData";

const FALLBACK_POSTS = [
  {
    id: 1,
    title: "Welcome to Royal Kings Education Centre",
    excerpt: "Discover a warm, Christian-centered community where every child is known and nurtured.",
    published_at: "2026",
  },
  {
    id: 2,
    title: "2025 Admissions Now Open",
    excerpt: "Join a school where education is our legacy — serving Wangige families since 2006.",
    published_at: "2025",
  },
  {
    id: 3,
    title: "Building a Sure Foundation",
    excerpt: INTRO.legacy.slice(0, 180) + "...",
    published_at: "2026",
  },
];

export default function BlogPage() {
  const { data, isLoading } = useBlogs();
  const posts = data?.data?.length ? data.data : FALLBACK_POSTS;

  return (
    <RichPage>
      <PageHero title="News & Stories" subtitle="Updates, inspiration, and stories from the Royal Kings family." />
      <SectionBlock>
        {isLoading && <p className="text-center">Loading posts...</p>}
        <div className="space-y-4 sm:space-y-6">
          {posts.map((post) => (
            <article key={post.id} className="rounded-2xl border border-[var(--rk-border)] bg-white p-5 sm:p-6">
              <h2 className="font-serif text-xl font-bold text-[var(--rk-purple-dark)] sm:text-2xl">{post.title}</h2>
              <p className="mt-2 text-xs text-[var(--rk-muted)] sm:text-sm">{post.published_at}</p>
              <p className="mt-3 text-sm text-[var(--rk-muted)] sm:text-base">{post.excerpt}</p>
            </article>
          ))}
        </div>
      </SectionBlock>
    </RichPage>
  );
}
