"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import type { LiveLibraryItem, LiveMediaKind } from "@/lib/liveEditor/types";

type MediaPickerProps = {
  open: boolean;
  title?: string;
  kind?: LiveMediaKind;
  currentUrl?: string;
  onClose: () => void;
  onSelect: (url: string, meta: { kind: "image" | "video" }) => void;
  onUpload: (file: File) => Promise<string>;
};

const FILTERS: Array<{ key: "all" | LiveLibraryItem["source"]; label: string }> = [
  { key: "all", label: "All" },
  { key: "upload", label: "Uploaded" },
  { key: "gallery", label: "CMS gallery" },
  { key: "campus", label: "Campus media" },
  { key: "public", label: "Site images" },
];

export function MediaPicker({
  open,
  title = "Replace media",
  kind = "any",
  currentUrl,
  onClose,
  onSelect,
  onUpload,
}: MediaPickerProps) {
  const [items, setItems] = useState<LiveLibraryItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [uploading, setUploading] = useState(false);
  const [source, setSource] = useState<(typeof FILTERS)[number]["key"]>("all");
  const [query, setQuery] = useState("");
  const [urlDraft, setUrlDraft] = useState(currentUrl || "");
  const fileRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (!open) return;
    setUrlDraft(currentUrl || "");
    setError(null);
    setLoading(true);
    void fetch("/api/live-editor/library", { cache: "no-store" })
      .then(async (res) => {
        if (!res.ok) throw new Error("Could not load media library");
        const data = (await res.json()) as { items?: LiveLibraryItem[] };
        setItems(Array.isArray(data.items) ? data.items : []);
      })
      .catch((e) => setError(e instanceof Error ? e.message : "Library failed"))
      .finally(() => setLoading(false));
  }, [open, currentUrl]);

  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") onClose();
    };
    document.addEventListener("keydown", onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", onKey);
      document.body.style.overflow = prev;
    };
  }, [open, onClose]);

  const filtered = useMemo(() => {
    return items.filter((item) => {
      if (kind === "image" && item.kind !== "image") return false;
      if (kind === "video" && item.kind !== "video") return false;
      if (source !== "all" && item.source !== source) return false;
      if (!query.trim()) return true;
      const q = query.trim().toLowerCase();
      return item.title.toLowerCase().includes(q) || item.url.toLowerCase().includes(q);
    });
  }, [items, kind, source, query]);

  if (!open) return null;

  const accept =
    kind === "video"
      ? "video/mp4,video/webm,video/quicktime"
      : kind === "image"
        ? "image/jpeg,image/png,image/webp,image/gif"
        : "image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime";

  const handleUpload = async (file: File | undefined) => {
    if (!file) return;
    setUploading(true);
    setError(null);
    try {
      const url = await onUpload(file);
      const uploadedKind: "image" | "video" = file.type.startsWith("video/") ? "video" : "image";
      onSelect(url, { kind: uploadedKind });
    } catch (e) {
      setError(e instanceof Error ? e.message : "Upload failed");
    } finally {
      setUploading(false);
      if (fileRef.current) fileRef.current.value = "";
    }
  };

  return (
    <div
      className="rk-media-picker-backdrop"
      role="dialog"
      aria-modal="true"
      aria-label={title}
      onClick={onClose}
    >
      <div className="rk-media-picker" onClick={(e) => e.stopPropagation()}>
        <header className="rk-media-picker__header">
          <div>
            <p className="rk-media-picker__eyebrow">Media library</p>
            <h2 className="rk-media-picker__title">{title}</h2>
          </div>
          <button type="button" className="rk-live-editor-btn" onClick={onClose}>
            Close
          </button>
        </header>

        <div className="rk-media-picker__actions">
          <button
            type="button"
            className="rk-live-editor-btn rk-live-editor-btn--primary"
            disabled={uploading}
            onClick={() => fileRef.current?.click()}
          >
            {uploading ? "Uploading…" : kind === "video" ? "Upload video" : "Upload new"}
          </button>
          <input
            ref={fileRef}
            type="file"
            accept={accept}
            className="hidden"
            onChange={(e) => void handleUpload(e.target.files?.[0])}
          />
          <input
            type="search"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Search media…"
            className="rk-media-picker__search"
            aria-label="Search media"
          />
        </div>

        <div className="rk-media-picker__filters">
          {FILTERS.map((f) => (
            <button
              key={f.key}
              type="button"
              className={`rk-media-picker__chip${source === f.key ? " is-active" : ""}`}
              onClick={() => setSource(f.key)}
            >
              {f.label}
            </button>
          ))}
        </div>

        {(kind === "video" || kind === "any") && (
          <div className="rk-media-picker__url-row">
            <input
              type="url"
              value={urlDraft}
              onChange={(e) => setUrlDraft(e.target.value)}
              placeholder="Paste Facebook Reel / video URL or uploaded file path"
              className="rk-media-picker__search"
              aria-label="Media URL"
            />
            <button
              type="button"
              className="rk-live-editor-btn rk-live-editor-btn--primary"
              disabled={!urlDraft.trim()}
              onClick={() => {
                const url = urlDraft.trim();
                const videoLike =
                  /\.(mp4|webm|mov)(\?|$)/i.test(url) ||
                  /facebook\.com\/(reel|watch|video)/i.test(url) ||
                  /tiktok\.com|youtube\.com|youtu\.be/i.test(url);
                onSelect(url, { kind: videoLike || kind === "video" ? "video" : "image" });
              }}
            >
              Use URL
            </button>
          </div>
        )}

        {error ? <p className="rk-media-picker__error">{error}</p> : null}
        {loading ? <p className="rk-media-picker__hint">Loading library…</p> : null}

        <div className="rk-media-picker__grid">
          {filtered.map((item) => {
            const selected = currentUrl === item.url;
            return (
              <button
                key={item.id}
                type="button"
                className={`rk-media-picker__item${selected ? " is-selected" : ""}`}
                onClick={() => onSelect(item.url, { kind: item.kind })}
                title={item.title}
              >
                {item.kind === "image" || item.thumb ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={item.thumb || item.url} alt="" />
                ) : (
                  <span className="rk-media-picker__video-fallback">Video</span>
                )}
                <span className="rk-media-picker__item-meta">
                  <span className="rk-media-picker__item-kind">{item.kind}</span>
                  <span className="rk-media-picker__item-title">{item.title}</span>
                </span>
              </button>
            );
          })}
          {!loading && filtered.length === 0 ? (
            <p className="rk-media-picker__hint">No matching media. Upload a new file or paste a URL.</p>
          ) : null}
        </div>
      </div>
    </div>
  );
}
