import { NextResponse } from "next/server";
import { promises as fs } from "fs";
import path from "path";
import { CAMPUS_PHOTOS, CAMPUS_REELS } from "@/content/campusMedia";
import { isLiveEditorAllowed, type LiveLibraryItem } from "@/lib/liveEditor/types";
import { uploadDirPath } from "@/lib/liveEditor/paths";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

async function listUploads(): Promise<LiveLibraryItem[]> {
  const dir = uploadDirPath();
  try {
    const names = await fs.readdir(dir);
    return names
      .filter((name) => /\.(jpe?g|png|webp|gif|mp4|webm|mov)$/i.test(name))
      .map((name) => {
        const lower = name.toLowerCase();
        const kind: "image" | "video" = /\.(mp4|webm|mov)$/.test(lower) ? "video" : "image";
        return {
          id: `upload:${name}`,
          url: `/uploads/live-editor/${name}`,
          title: name,
          kind,
          source: "upload" as const,
          thumb: kind === "image" ? `/uploads/live-editor/${name}` : undefined,
        };
      })
      .sort((a, b) => b.id.localeCompare(a.id));
  } catch {
    return [];
  }
}

async function listPublicImages(): Promise<LiveLibraryItem[]> {
  const roots = [
    path.join(process.cwd(), "public", "images", "facebook-2026"),
    path.join(process.cwd(), "public", "images"),
  ];
  const out: LiveLibraryItem[] = [];
  const seen = new Set<string>();

  for (const root of roots) {
    try {
      const names = await fs.readdir(root);
      for (const name of names) {
        if (!/\.(jpe?g|png|webp|gif)$/i.test(name)) continue;
        const rel =
          root.endsWith("facebook-2026")
            ? `/images/facebook-2026/${name}`
            : `/images/${name}`;
        if (seen.has(rel)) continue;
        seen.add(rel);
        out.push({
          id: `public:${rel}`,
          url: rel,
          title: name,
          kind: "image",
          source: "public",
          thumb: rel,
        });
      }
    } catch {
      /* folder may be missing */
    }
  }
  return out;
}

async function listPremiumGallery(): Promise<LiveLibraryItem[]> {
  const api = process.env.NEXT_PUBLIC_API_URL || process.env.API_URL;
  if (!api) return [];
  try {
    const res = await fetch(`${api.replace(/\/$/, "")}/website/gallery?premium=1&per_page=48`, {
      cache: "no-store",
    });
    if (!res.ok) return [];
    const json = (await res.json()) as {
      data?: Array<{
        id: number | string;
        url?: string;
        optimized_url?: string | null;
        title?: string | null;
        alt_text?: string | null;
        type?: string;
      }>;
    };
    const rows = Array.isArray(json.data) ? json.data : [];
    return rows
      .map((row) => {
        const url = row.optimized_url || row.url;
        if (!url) return null;
        return {
          id: `gallery:${row.id}`,
          url,
          title: row.title || row.alt_text || `Gallery ${row.id}`,
          kind: "image" as const,
          source: "gallery" as const,
          thumb: url,
        };
      })
      .filter(Boolean) as LiveLibraryItem[];
  } catch {
    return [];
  }
}

export async function GET() {
  if (!isLiveEditorAllowed()) {
    return NextResponse.json({ error: "Live editor disabled" }, { status: 403 });
  }

  const campus: LiveLibraryItem[] = [
    ...CAMPUS_PHOTOS.map((p) => ({
      id: `campus-photo:${p.id}`,
      url: p.src,
      title: p.title,
      kind: "image" as const,
      source: "campus" as const,
      thumb: p.src,
    })),
    ...CAMPUS_REELS.map((r) => ({
      id: `campus-reel:${r.id}`,
      url: r.facebookUrl,
      title: r.title,
      kind: "video" as const,
      source: "campus" as const,
      thumb: r.poster,
    })),
  ];

  const [uploads, publicImages, gallery] = await Promise.all([
    listUploads(),
    listPublicImages(),
    listPremiumGallery(),
  ]);

  const items = [...uploads, ...gallery, ...campus, ...publicImages];
  return NextResponse.json({ items });
}
