import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { brandingApi } from '../api/branding.api';
import type { AppBranding } from '../types/branding';
import { mergePortalColors, type BrandColorOverrides } from '../utils/mergePortalColors';

export interface BrandingContextValue {
  branding: AppBranding | null;
  schoolName: string;
  logoUrl: string | null;
  loginBackgroundUrl: string | null;
  colorOverrides: BrandColorOverrides;
  loading: boolean;
  refresh: () => Promise<void>;
}

const BrandingContext = createContext<BrandingContextValue | undefined>(undefined);

export const BrandingProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [branding, setBranding] = useState<AppBranding | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = async () => {
    setLoading(true);
    try {
      const res = await brandingApi.getAppBranding();
      // GET /app-branding returns a flat payload (no { success, data } wrapper).
      if (res && typeof res === 'object' && 'school_name' in res) {
        setBranding(res as unknown as AppBranding);
      } else if (res.success && res.data) {
        setBranding(res.data);
      }
    } catch {
      /* keep defaults */
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void refresh();
  }, []);

  const value = useMemo<BrandingContextValue>(
    () => ({
      branding,
      schoolName: branding?.school_name?.trim() || 'School ERP',
      logoUrl: branding?.logo_url ?? null,
      loginBackgroundUrl: branding?.login_background_url ?? null,
      colorOverrides: mergePortalColors(branding?.colors),
      loading,
      refresh,
    }),
    [branding, loading],
  );

  return <BrandingContext.Provider value={value}>{children}</BrandingContext.Provider>;
};

export function useBranding(): BrandingContextValue {
  const ctx = useContext(BrandingContext);
  if (!ctx) {
    throw new Error('useBranding must be used within BrandingProvider');
  }
  return ctx;
}
