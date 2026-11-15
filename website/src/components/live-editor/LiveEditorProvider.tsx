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
import { EMPTY_OVERRIDES, type LiveOverrides, isLiveEditorAllowed } from "@/lib/liveEditor/types";

type LiveEditorContextValue = {
  allowed: boolean;
  editing: boolean;
  dirty: boolean;
  saving: boolean;
  overrides: LiveOverrides;
  setEditing: (v: boolean) => void;
  getText: (key: string, fallback: string) => string;
  getImage: (key: string, fallback: string) => string;
  setText: (key: string, value: string) => void;
  setImage: (key: string, value: string) => void;
  save: () => Promise<void>;
  reload: () => Promise<void>;
  uploadImage: (key: string, file: File) => Promise<string>;
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
      const data = (await res.json()) as LiveOverrides;
      const normalized: LiveOverrides = {
        texts: data.texts ?? {},
        images: data.images ?? {},
        updatedAt: data.updatedAt ?? null,
      };
      setOverrides(normalized);
      setBaseline(JSON.stringify(normalized));
    } catch {
      /* keep defaults */
    }
  }, [allowed]);

  useEffect(() => {
    if (!allowed) return;
    // Load draft overrides once when the editor is available.
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
      const data = (await res.json()) as LiveOverrides;
      setOverrides(data);
      setBaseline(JSON.stringify(data));
    } finally {
      setSaving(false);
    }
  }, [allowed, overrides]);

  const uploadImage = useCallback(
    async (key: string, file: File) => {
      const form = new FormData();
      form.append("file", file);
      form.append("key", key);
      const res = await fetch("/api/live-editor/upload", { method: "POST", body: form });
      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error((err as { error?: string }).error || "Upload failed");
      }
      const data = (await res.json()) as { url: string };
      setImage(key, data.url);
      return data.url;
    },
    [setImage],
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
      setText,
      setImage,
      save,
      reload: allowed ? reload : noopAsync,
      uploadImage,
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
      setText,
      setImage,
      save,
      reload,
      uploadImage,
    ],
  );

  return <LiveEditorContext.Provider value={value}>{children}</LiveEditorContext.Provider>;
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
