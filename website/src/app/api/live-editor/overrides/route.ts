import { NextResponse } from "next/server";
import { promises as fs } from "fs";
import { EMPTY_OVERRIDES, type LiveOverrides, isLiveEditorAllowed } from "@/lib/liveEditor/types";
import { overridesFilePath } from "@/lib/liveEditor/paths";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

async function readOverrides(): Promise<LiveOverrides> {
  try {
    const raw = await fs.readFile(overridesFilePath(), "utf8");
    const parsed = JSON.parse(raw) as Partial<LiveOverrides>;
    return {
      texts: parsed.texts ?? {},
      images: parsed.images ?? {},
      updatedAt: parsed.updatedAt ?? null,
    };
  } catch {
    return { ...EMPTY_OVERRIDES, texts: {}, images: {} };
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
    texts: body.texts && typeof body.texts === "object" ? body.texts : {},
    images: body.images && typeof body.images === "object" ? body.images : {},
    updatedAt: new Date().toISOString(),
  };

  await fs.writeFile(overridesFilePath(), `${JSON.stringify(next, null, 2)}\n`, "utf8");
  return NextResponse.json(next);
}
