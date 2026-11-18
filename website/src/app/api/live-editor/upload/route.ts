import { NextResponse } from "next/server";
import { promises as fs } from "fs";
import path from "path";
import { isLiveEditorAllowed } from "@/lib/liveEditor/types";
import { uploadDirPath } from "@/lib/liveEditor/paths";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

const IMAGE_TYPES = new Set(["image/jpeg", "image/png", "image/webp", "image/gif"]);
const VIDEO_TYPES = new Set(["video/mp4", "video/webm", "video/quicktime"]);

export async function POST(request: Request) {
  if (!isLiveEditorAllowed()) {
    return NextResponse.json({ error: "Live editor disabled" }, { status: 403 });
  }

  const form = await request.formData();
  const file = form.get("file");
  const key = String(form.get("key") || "media");

  if (!(file instanceof File)) {
    return NextResponse.json({ error: "Missing file" }, { status: 400 });
  }

  const isImage = IMAGE_TYPES.has(file.type);
  const isVideo = VIDEO_TYPES.has(file.type);
  if (!isImage && !isVideo) {
    return NextResponse.json(
      { error: "Unsupported type. Use JPG/PNG/WebP/GIF or MP4/WebM." },
      { status: 400 },
    );
  }

  const maxBytes = isVideo ? 80 * 1024 * 1024 : 12 * 1024 * 1024;
  if (file.size > maxBytes) {
    return NextResponse.json(
      { error: isVideo ? "Video too large (max 80MB)" : "Image too large (max 12MB)" },
      { status: 400 },
    );
  }

  const dir = uploadDirPath();
  await fs.mkdir(dir, { recursive: true });

  const ext = (() => {
    if (file.type === "image/png") return "png";
    if (file.type === "image/webp") return "webp";
    if (file.type === "image/gif") return "gif";
    if (file.type === "video/webm") return "webm";
    if (file.type === "video/quicktime") return "mov";
    if (isVideo) return "mp4";
    return "jpg";
  })();

  const safeKey = key.replace(/[^a-zA-Z0-9._-]+/g, "-").slice(0, 80) || "media";
  const filename = `${safeKey}-${Date.now()}.${ext}`;
  const buffer = Buffer.from(await file.arrayBuffer());
  await fs.writeFile(path.join(dir, filename), buffer);

  const url = `/uploads/live-editor/${filename}`;
  return NextResponse.json({ url, kind: isVideo ? "video" : "image" });
}
