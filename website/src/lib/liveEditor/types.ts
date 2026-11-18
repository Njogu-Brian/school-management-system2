export type LiveOverrides = {
  texts: Record<string, string>;
  images: Record<string, string>;
  videos: Record<string, string>;
  updatedAt: string | null;
};

export const EMPTY_OVERRIDES: LiveOverrides = {
  texts: {},
  images: {},
  videos: {},
  updatedAt: null,
};

export type LiveMediaKind = "image" | "video" | "any";

export type LiveLibraryItem = {
  id: string;
  url: string;
  title: string;
  kind: "image" | "video";
  source: "campus" | "upload" | "gallery" | "public";
  thumb?: string;
};

export function normalizeOverrides(raw: Partial<LiveOverrides> | null | undefined): LiveOverrides {
  return {
    texts: raw?.texts && typeof raw.texts === "object" ? raw.texts : {},
    images: raw?.images && typeof raw.images === "object" ? raw.images : {},
    videos: raw?.videos && typeof raw.videos === "object" ? raw.videos : {},
    updatedAt: raw?.updatedAt ?? null,
  };
}

/** Live editor is available in local development, or when NEXT_PUBLIC_LIVE_EDITOR=1. */
export function isLiveEditorAllowed(): boolean {
  if (typeof process !== "undefined" && process.env.NEXT_PUBLIC_LIVE_EDITOR === "1") {
    return true;
  }
  if (typeof process !== "undefined" && process.env.NODE_ENV === "development") {
    return true;
  }
  return false;
}
