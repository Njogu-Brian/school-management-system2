import { NextResponse } from "next/server";
import { promises as fs } from "fs";
import path from "path";
import { isLiveEditorAllowed } from "@/lib/liveEditor/types";
import { uploadDirPath } from "@/lib/liveEditor/paths";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

const ALLOWED = new Set(["image/jpeg", "image/png", "image/webp", "image/gif"]);

export async function POST(request: Request) {
  if (!isLiveEditorAllowed()) {
    return NextResponse.json({ error: "Live editor disabled" }, { status: 403 });
  }

  const form = await request.formData();
  const file = form.get("file");
  const key = String(form.get("key") || "image");

  if (!(file instanceof File)) {
    return NextResponse.json({ error: "Missing file" }, { status: 400 });
  }
  if (!ALLOWED.has(file.type)) {
    return NextResponse.json({ error: "Unsupported image type" }, { status: 400 });
  }
  if (file.size > 12 * 1024 * 1024) {
    return NextResponse.json({ error: "Image too large (max 12MB)" }, { status: 400 });
  }

  const dir = uploadDirPath();
  await fs.mkdir(dir, { recursive: true });

  const ext =
    file.type === "image/png"
      ? "png"
      : file.type === "image/webp"
        ? "webp"
        : file.type === "image/gif"
          ? "gif"
          : "jpg";

  const safeKey = key.replace(/[^a-zA-Z0-9._-]+/g, "-").slice(0, 80) || "image";
  const filename = `${safeKey}-${Date.now()}.${ext}`;
  const buffer = Buffer.from(await file.arrayBuffer());
  await fs.writeFile(path.join(dir, filename), buffer);

  const url = `/uploads/live-editor/${filename}`;
  return NextResponse.json({ url });
}
