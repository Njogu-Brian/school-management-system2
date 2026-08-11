import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';
import { apiClient } from '../api/client';
import { resolveSchoolCode } from '../api/schools.api';
import {
  LEGACY_ANCHOR_SCHOOL,
  REQUIRE_SCHOOL_CODE,
} from '../config/env';
import {
  clearSchoolContext,
  getSchoolContext,
  saveSchoolContext,
} from '../storage/schoolStorage';
import { clearAuthData } from '../storage/authStorage';
import type { ResolvedSchool } from '../types';

type SchoolStatus = 'initializing' | 'needs_code' | 'ready';

type SchoolContextValue = {
  status: SchoolStatus;
  school: ResolvedSchool | null;
  error: string | null;
  submitting: boolean;
  /** Resolve a code, persist it, and retarget the API client. */
  selectSchoolByCode: (code: string) => Promise<boolean>;
  /** Clear school + auth so the user can pick another school. */
  clearSchool: () => Promise<void>;
};

const SchoolContext = createContext<SchoolContextValue | null>(null);

export const SchoolProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [status, setStatus] = useState<SchoolStatus>('initializing');
  const [school, setSchool] = useState<ResolvedSchool | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    let cancelled = false;
    void (async () => {
      try {
        const stored = await getSchoolContext();
        if (cancelled) return;
        if (stored) {
          apiClient.setBaseURL(stored.apiBaseUrl);
          setSchool(stored);
          setStatus('ready');
          return;
        }
        if (!REQUIRE_SCHOOL_CODE) {
          // Keep existing Play Store users on Royal Kings without forcing a code.
          await saveSchoolContext(LEGACY_ANCHOR_SCHOOL);
          if (cancelled) return;
          apiClient.setBaseURL(LEGACY_ANCHOR_SCHOOL.apiBaseUrl);
          setSchool(LEGACY_ANCHOR_SCHOOL);
          setStatus('ready');
          return;
        }
        setStatus('needs_code');
      } catch {
        if (!cancelled) {
          setStatus(REQUIRE_SCHOOL_CODE ? 'needs_code' : 'ready');
          if (!REQUIRE_SCHOOL_CODE) {
            apiClient.setBaseURL(LEGACY_ANCHOR_SCHOOL.apiBaseUrl);
            setSchool(LEGACY_ANCHOR_SCHOOL);
          }
        }
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const selectSchoolByCode = useCallback(async (code: string): Promise<boolean> => {
    setSubmitting(true);
    setError(null);
    try {
      const resolved = await resolveSchoolCode(code);
      await saveSchoolContext(resolved);
      apiClient.setBaseURL(resolved.apiBaseUrl);
      setSchool(resolved);
      setStatus('ready');
      return true;
    } catch (e: unknown) {
      const message =
        e && typeof e === 'object' && 'message' in e && typeof (e as { message: unknown }).message === 'string'
          ? (e as { message: string }).message
          : 'Could not find that school.';
      setError(message);
      return false;
    } finally {
      setSubmitting(false);
    }
  }, []);

  const clearSchool = useCallback(async () => {
    await clearAuthData();
    await clearSchoolContext();
    setSchool(null);
    setError(null);
    setStatus('needs_code');
  }, []);

  const value = useMemo<SchoolContextValue>(
    () => ({
      status,
      school,
      error,
      submitting,
      selectSchoolByCode,
      clearSchool,
    }),
    [status, school, error, submitting, selectSchoolByCode, clearSchool],
  );

  return <SchoolContext.Provider value={value}>{children}</SchoolContext.Provider>;
};

export function useSchool(): SchoolContextValue {
  const ctx = useContext(SchoolContext);
  if (!ctx) {
    throw new Error('useSchool must be used within SchoolProvider');
  }
  return ctx;
}
