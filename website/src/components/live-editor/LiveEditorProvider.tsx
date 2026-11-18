"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from "react";
import { useSearchParams } from "next/navigation";
import {
  EMPTY_OVERRIDES,
  type LiveMediaKind,
  type LiveOverrides,
  isLiveEditorAllowed,
  normalizeOverrides,
} from "@/lib/liveEditor/types";
import { MediaPicker } from "./MediaPicker";

type MediaPickerRequest = {
  key: string;
  kind: LiveMediaKind;
  currentUrl?: string;
  title?: string;
  target: "image" | "video";
};

type LiveEditorContextValue = {
  allowed: boolean;
  editing: boolean;
  dirty: boolean;
  saving: boolean;
  overrides: LiveOverrides;
  setEditing: (v: boolean) => void;
  getText: (key: string, fallback: string) => string;
  getImage: (key: string, fallback: string) => string;
  getVideo: (key: string, fallback: string) => string;
  setText: (key: string, value: string) => void;
  setImage: (key: string, value: string) => void;
  setVideo: (key: string, value: string) => void;
  save: () => Promise<void>;
  reload: () => Promise<void>;
  uploadMedia: (key: string, file: File) => Promise<string>;
  openMediaPicker: (req: Omit<MediaPickerRequest, "target"> & { target?: "image" | "video" }) => void;
};

const LiveEditorContext = createContext<LiveEditorContextValue | null>(null);

const STORAGE_KEY = "rk-live-editor";

const noopAsync = async () => undefined;

export function LiveEditorProvider({ children }: { children: ReactNode }) {
  const allowed = isLiveEditorAllowed();
  const searchParams = useSearchParams();
  const editParam = searchParams.get("edit") === "1";

  const [overrides, setOverrides] = useState<LiveOverrides>(EMPTY_OVERRIDES);
  const [baseline, setBaseline] = useState(() => JSON.stringify(EMPTY_OVERRIDES));
  const [saving, setSaving] = useState(false);
  const [userEditing, setUserEditing] = useState<boolean | null>(null);
  const [storageEditing, setStorageEditing] = useState(false);
  const [picker, setPicker] = useState<MediaPickerRequest | null>(null);

  useEffect(() => {
    if (!allowed) return;
    // Sync edit mode from localStorage after hydration.
    // eslint-disable-next-line react-hooks/set-state-in-effect -- intentional client-only preference restore
    setStorageEditing(localStorage.getItem(STORAGE_KEY) === "1");
  }, [allowed]);

  const editing = Boolean(allowed && (userEditing ?? (editParam || storageEditing)));
  const dirty = useMemo(() => JSON.stringify(overrides) !== baseline, [overrides, baseline]);

  const reload = useCallback(async () => {
    if (!allowed) return;
    try {
      const res = await fetch("/api/live-editor/overrides", { cache: "no-store" });
      if (!res.ok) return;
      const data = normalizeOverrides((await res.json()) as LiveOverrides);
      setOverrides(data);
      setBaseline(JSON.stringify(data));
    } catch {
      /* keep defaults */
    }
  }, [allowed]);

  useEffect(() => {
    if (!allowed) return;
    void reload();
  }, [allowed, reload]);

  const setEditing = useCallback(
    (v: boolean) => {
      if (!allowed) return;
      setUserEditing(v);
      localStorage.setItem(STORAGE_KEY, v ? "1" : "0");
      const url = new URL(window.location.href);
      if (v) url.searchParams.set("edit", "1");
      else url.searchParams.delete("edit");
      window.history.replaceState({}, "", url.toString());
    },
    [allowed],
  );

  const getText = useCallback(
    (key: string, fallback: string) => overrides.texts[key] ?? fallback,
    [overrides.texts],
  );

  const getImage = useCallback(
    (key: string, fallback: string) => overrides.images[key] ?? fallback,
    [overrides.images],
  );

  const getVideo = useCallback(
    (key: string, fallback: string) => overrides.videos[key] ?? fallback,
    [overrides.videos],
  );

  const setText = useCallback((key: string, value: string) => {
    setOverrides((prev) => ({
      ...prev,
      texts: { ...prev.texts, [key]: value },
    }));
  }, []);

  const setImage = useCallback((key: string, value: string) => {
    setOverrides((prev) => ({
      ...prev,
      images: { ...prev.images, [key]: value },
    }));
  }, []);

  const setVideo = useCallback((key: string, value: string) => {
    setOverrides((prev) => ({
      ...prev,
      videos: { ...prev.videos, [key]: value },
    }));
  }, []);

  const save = useCallback(async () => {
    if (!allowed) return;
    setSaving(true);
    try {
      const res = await fetch("/api/live-editor/overrides", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(overrides),
      });
      if (!res.ok) throw new Error("Save failed");
      const data = normalizeOverrides((await res.json()) as LiveOverrides);
      setOverrides(data);
      setBaseline(JSON.stringify(data));
    } finally {
      setSaving(false);
    }
  }, [allowed, overrides]);

  const uploadMedia = useCallback(async (key: string, file: File) => {
    const form = new FormData();
    form.append("file", file);
    form.append("key", key);
    const res = await fetch("/api/live-editor/upload", { method: "POST", body: form });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error((err as { error?: string }).error || "Upload failed");
    }
    const data = (await res.json()) as { url: string };
    return data.url;
  }, []);

  const openMediaPicker = useCallback(
    (req: Omit<MediaPickerRequest, "target"> & { target?: "image" | "video" }) => {
      if (!allowed || !editing) return;
      setPicker({
        key: req.key,
        kind: req.kind ?? "image",
        currentUrl: req.currentUrl,
        title: req.title,
        target: req.target ?? (req.kind === "video" ? "video" : "image"),
      });
    },
    [allowed, editing],
  );

  const value = useMemo<LiveEditorContextValue>(
    () => ({
      allowed,
      editing,
      dirty,
      saving,
      overrides,
      setEditing,
      getText,
      getImage,
      getVideo,
      setText,
      setImage,
      setVideo,
      save,
      reload: allowed ? reload : noopAsync,
      uploadMedia,
      openMediaPicker,
    }),
    [
      allowed,
      editing,
      dirty,
      saving,
      overrides,
      setEditing,
      getText,
      getImage,
      getVideo,
      setText,
      setImage,
      setVideo,
      save,
      reload,
      uploadMedia,
      openMediaPicker,
    ],
  );

  return (
    <LiveEditorContext.Provider value={value}>
      {children}
      {picker ? (
        <MediaPicker
          open
          title={picker.title || (picker.target === "video" ? "Replace video" : "Replace photo")}
          kind={picker.kind}
          currentUrl={picker.currentUrl}
          onClose={() => setPicker(null)}
          onUpload={(file) => uploadMedia(picker.key, file)}
          onSelect={(url, meta) => {
            if (picker.target === "video" || meta.kind === "video") {
              setVideo(picker.key, url);
            } else {
              setImage(picker.key, url);
            }
            setPicker(null);
          }}
        />
      ) : null}
    </LiveEditorContext.Provider>
  );
}

export function useLiveEditor() {
  const ctx = useContext(LiveEditorContext);
  if (!ctx) {
    throw new Error("useLiveEditor must be used within LiveEditorProvider");
  }
  return ctx;
}

export function useLiveEditorOptional(): LiveEditorContextValue | null {
  return useContext(LiveEditorContext);
}

export function useLiveText(key: string, fallback: string): string {
  const ctx = useLiveEditorOptional();
  if (!ctx) return fallback;
  return ctx.getText(key, fallback);
}

export function useLiveImage(key: string, fallback: string): string {
  const ctx = useLiveEditorOptional();
  if (!ctx) return fallback;
  return ctx.getImage(key, fallback);
}

export function useLiveVideo(key: string, fallback: string): string {
  const ctx = useLiveEditorOptional();
  if (!ctx) return fallback;
  return ctx.getVideo(key, fallback);
}
