import { NextResponse } from "next/server";
import { promises as fs } from "fs";
import {
  EMPTY_OVERRIDES,
  type LiveOverrides,
  isLiveEditorAllowed,
  normalizeOverrides,
} from "@/lib/liveEditor/types";
import { overridesFilePath } from "@/lib/liveEditor/paths";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

async function readOverrides(): Promise<LiveOverrides> {
  try {
    const raw = await fs.readFile(overridesFilePath(), "utf8");
    return normalizeOverrides(JSON.parse(raw) as Partial<LiveOverrides>);
  } catch {
    return { ...EMPTY_OVERRIDES, texts: {}, images: {}, videos: {} };
  }
}

export async function GET() {
  const data = await readOverrides();
  return NextResponse.json(data);
}

export async function PUT(request: Request) {
  if (!isLiveEditorAllowed()) {
    return NextResponse.json({ error: "Live editor disabled" }, { status: 403 });
  }

  const body = (await request.json()) as Partial<LiveOverrides>;
  const next: LiveOverrides = {
    ...normalizeOverrides(body),
    updatedAt: new Date().toISOString(),
  };

  await fs.writeFile(overridesFilePath(), `${JSON.stringify(next, null, 2)}\n`, "utf8");
  return NextResponse.json(next);
}
