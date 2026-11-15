export type LiveOverrides = {
  texts: Record<string, string>;
  images: Record<string, string>;
  updatedAt: string | null;
};

export const EMPTY_OVERRIDES: LiveOverrides = {
  texts: {},
  images: {},
  updatedAt: null,
};

export function isLiveEditorAllowed(): boolean {
  if (typeof process !== "undefined" && process.env.NEXT_PUBLIC_LIVE_EDITOR === "1") {
    return true;
  }
  if (typeof process !== "undefined" && process.env.NODE_ENV === "development") {
    return true;
  }
  return false;
}
