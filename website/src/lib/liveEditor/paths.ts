import path from "path";

export function overridesFilePath() {
  return path.join(process.cwd(), "src", "content", "liveOverrides.json");
}

export function uploadDirPath() {
  return path.join(process.cwd(), "public", "uploads", "live-editor");
}
