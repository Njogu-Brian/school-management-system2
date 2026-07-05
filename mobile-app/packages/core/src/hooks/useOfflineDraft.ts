import { useCallback, useEffect, useRef, useState } from 'react';
import { clearDraft, loadDraft, saveDraft } from '../sync/draftStorage';

export function useOfflineDraft<T>(key: string | null, options?: { debounceMs?: number }) {
  const debounceMs = options?.debounceMs ?? 500;
  const [draft, setDraftState] = useState<T | null>(null);
  const [loaded, setLoaded] = useState(false);
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    if (!key) {
      setLoaded(true);
      return;
    }
    setLoaded(false);
    void loadDraft<T>(key).then((value) => {
      if (value != null) setDraftState(value);
      setLoaded(true);
    });
  }, [key]);

  const persist = useCallback(
    (value: T) => {
      if (!key) return;
      if (timerRef.current) clearTimeout(timerRef.current);
      timerRef.current = setTimeout(() => {
        void saveDraft(key, value);
      }, debounceMs);
    },
    [key, debounceMs],
  );

  const setDraft = useCallback(
    (value: T | ((prev: T | null) => T)) => {
      setDraftState((prev) => {
        const next = typeof value === 'function' ? (value as (p: T | null) => T)(prev) : value;
        persist(next);
        return next;
      });
    },
    [persist],
  );

  const clearDraftStorage = useCallback(async () => {
    if (key) await clearDraft(key);
    setDraftState(null);
  }, [key]);

  return { draft, setDraft, loaded, clearDraft: clearDraftStorage };
}
