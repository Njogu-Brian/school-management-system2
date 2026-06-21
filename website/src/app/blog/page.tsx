"use client";

import { SiteShell } from "@/components/layout/SiteShell";
import { useBlogs } from "@/hooks/useWebsiteData";
import Link from "next/link";

export default function BlogPage() {
  const { data, isLoading } = useBlogs();

  return (
    <SiteShell>
      <section className="bg-[#2a1145] py-16 text-white"><div className="mx-auto max-w-6xl px-4 text-center"><h1 className="font-serif text-4xl font-bold">Blog</h1></div></section>
      <section className="mx-auto max-w-4xl space-y-6 px-4 py-16">
        {isLoading && <p>Loading posts...</p>}
        {(data?.data || []).map((post) => (
          <article key={post.id} className="rounded-2xl border border-[#e8dff5] p-6">
            <h2 className="font-serif text-xl font-bold text-[#2a1145]">{post.title}</h2>
            <p className="mt-2 text-sm text-[#4a3a5c]">{post.published_at}</p>
            <p className="mt-3 text-[#4a3a5c]">{post.excerpt}</p>
          </article>
        ))}
      </section>
    </SiteShell>
  );
}
