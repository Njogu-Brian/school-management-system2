import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import type { PersistedSessionMeta } from '../types';
import { sessionsApi } from '../api/sessions.api';
import {
  clearAuthData,
  getSessionMeta,
  saveSessionMeta,
} from '../storage/authStorage';
import { getToken, saveToken } from '../storage/secureStorage';
import { isSessionExpired, parseExpiresAt } from './sessionPolicy';

interface SetSessionInput {
  token: string;
  /** ISO8601 server token expiry from the login response. */
  expiresAt?: string | null;
  rememberMe?: boolean;
}

export interface SessionContextValue {
  /** True once the persisted session has been read from storage on cold start. */
  hydrated: boolean;
  /** Current access token, or null when there is no (valid) session. */
  token: string | null;
  /** Token present AND not expired (server expiry / absolute age / idle). */
  isValid: boolean;
  /** Persist a freshly issued session (called by the auth layer after login). */
  setSession: (input: SetSessionInput) => Promise<void>;
  /** Wipe the session from memory and storage. */
  clearSession: () => Promise<void>;
  /** Record activity to defer the idle timeout. */
  touch: () => Promise<void>;
  /**
   * Forward-compatible refresh hook. No refresh endpoint exists yet, so this is a
   * no-op returning `false`; wire it to `POST /token/refresh` when the backend ships it.
   */
  refresh: () => Promise<boolean>;
}

const SessionContext = createContext<SessionContextValue | undefined>(undefined);

export const SessionProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [hydrated, setHydrated] = useState(false);
  const [token, setToken] = useState<string | null>(null);
  const [meta, setMeta] = useState<PersistedSessionMeta | null>(null);
  const lastTouchRef = useRef(0);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const [storedToken, storedMeta] = await Promise.all([getToken(), getSessionMeta()]);
      if (cancelled) {
        return;
      }
      if (storedToken && !isSessionExpired(storedMeta)) {
        setToken(storedToken);
        setMeta(storedMeta);
      } else if (storedToken || storedMeta) {
        // Stale/expired remnants — clear so we start from a clean slate.
        await clearAuthData();
      }
      setHydrated(true);
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const setSession = useCallback(async (input: SetSessionInput) => {
    const now = Date.now();
    const nextMeta: PersistedSessionMeta = {
      expiresAt: parseExpiresAt(input.expiresAt),
      startedAt: now,
      lastActivityAt: now,
      rememberMe: input.rememberMe ?? false,
    };
    await saveToken(input.token);
    await saveSessionMeta(nextMeta);
    setToken(input.token);
    setMeta(nextMeta);
  }, []);

  const clearSession = useCallback(async () => {
    await clearAuthData();
    setToken(null);
    setMeta(null);
  }, []);

  const touch = useCallback(async () => {
    const now = Date.now();
    // Throttle writes to at most once per minute.
    if (now - lastTouchRef.current < 60_000) {
      return;
    }
    lastTouchRef.current = now;
    setMeta((prev) => {
      if (!prev) {
        return prev;
      }
      const updated = { ...prev, lastActivityAt: now };
      void saveSessionMeta(updated);
      return updated;
    });
  }, []);

  const refresh = useCallback(async () => {
    if (!token) {
      return false;
    }
    try {
      const res = await sessionsApi.refresh();
      if (!res.success || !res.data?.token) {
        await clearSession();
        return false;
      }
      await setSession({
        token: res.data.token,
        expiresAt: res.data.expires_at,
        rememberMe: meta?.rememberMe,
      });
      return true;
    } catch {
      await clearSession();
      return false;
    }
  }, [token, meta?.rememberMe, setSession, clearSession]);

  const isValid = token != null && !isSessionExpired(meta);

  const value = useMemo<SessionContextValue>(
    () => ({ hydrated, token, isValid, setSession, clearSession, touch, refresh }),
    [hydrated, token, isValid, setSession, clearSession, touch, refresh],
  );

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
};

export function useSession(): SessionContextValue {
  const ctx = useContext(SessionContext);
  if (!ctx) {
    throw new Error('useSession must be used within a SessionProvider');
  }
  return ctx;
}
