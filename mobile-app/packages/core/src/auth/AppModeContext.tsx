import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';
import type { User } from '../types';
import { getAppMode, setAppMode as persistAppMode, type AppMode } from '../storage/appModeStorage';
import { useCurrentUser } from './hooks';

export type { AppMode };

/** Whether a user can toggle between Work and Home shells (dual identity). */
export function userHasDualIdentity(user: User | null | undefined): boolean {
  if (!user) return false;
  const canWork = user.canWorkMode ?? Boolean(user.staffId);
  const canHome = user.canHomeMode ?? Boolean(user.parentId);
  return canWork && canHome;
}

/** Resolve the shell a user should see given their capabilities and persisted preference. */
export function resolveEffectiveMode(user: User | null | undefined, persisted: AppMode | null): AppMode {
  if (!user) return 'work';
  const canWork = user.canWorkMode ?? Boolean(user.staffId);
  const canHome = user.canHomeMode ?? Boolean(user.parentId);
  if (canWork && canHome) {
    return persisted ?? 'work';
  }
  if (canHome && !canWork) {
    return 'home';
  }
  return 'work';
}

interface AppModeContextValue {
  /** Effective mode after considering the user's capabilities. */
  mode: AppMode;
  /** Whether the user can switch modes at all. */
  canSwitch: boolean;
  /** True until the persisted preference has been read from storage. */
  ready: boolean;
  setMode: (mode: AppMode) => Promise<void>;
  toggle: () => Promise<void>;
}

const AppModeContext = createContext<AppModeContextValue | undefined>(undefined);

export const AppModeProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const user = useCurrentUser();
  const [persisted, setPersisted] = useState<AppMode | null>(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const stored = await getAppMode();
      if (!cancelled) {
        setPersisted(stored);
        setReady(true);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const canSwitch = userHasDualIdentity(user);
  const mode = resolveEffectiveMode(user, persisted);

  const setMode = useCallback(async (next: AppMode) => {
    setPersisted(next);
    await persistAppMode(next);
  }, []);

  const toggle = useCallback(async () => {
    await setMode(mode === 'work' ? 'home' : 'work');
  }, [mode, setMode]);

  const value = useMemo<AppModeContextValue>(
    () => ({ mode, canSwitch, ready, setMode, toggle }),
    [mode, canSwitch, ready, setMode, toggle],
  );

  return <AppModeContext.Provider value={value}>{children}</AppModeContext.Provider>;
};

export function useAppMode(): AppModeContextValue {
  const ctx = useContext(AppModeContext);
  if (!ctx) {
    throw new Error('useAppMode must be used within an AppModeProvider');
  }
  return ctx;
}
