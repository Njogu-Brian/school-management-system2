import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { brandingApi } from '../api/branding.api';
import { useSchoolOptional } from '../auth/SchoolContext';
import { isCombinedApp } from '../config/env';
import { PRODUCT, PRODUCT_COLOR_OVERRIDES } from '../config/product';
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

function productFallbackName(): string {
  return isCombinedApp() ? PRODUCT.name : 'School ERP';
}

export const BrandingProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const schoolCtx = useSchoolOptional();
  const [branding, setBranding] = useState<AppBranding | null>(null);
  const [loading, setLoading] = useState(true);

  const schoolReady = !schoolCtx || schoolCtx.status === 'ready';
  const schoolCode = schoolCtx?.school?.code ?? null;
  const resolvedName = schoolCtx?.school?.name ?? null;
  const resolvedLogo = schoolCtx?.school?.branding.logoUrl ?? null;
  const resolvedPrimary = schoolCtx?.school?.branding.primaryColor ?? null;

  const refresh = useCallback(async () => {
    if (schoolCtx && schoolCtx.status !== 'ready') {
      setBranding(null);
      setLoading(false);
      return;
    }
    setLoading(true);
    try {
      const res = await brandingApi.getAppBranding();
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
  }, [schoolCtx]);

  useEffect(() => {
    void refresh();
  }, [refresh, schoolReady, schoolCode]);

  const value = useMemo<BrandingContextValue>(() => {
    const portalColors = mergePortalColors(branding?.colors);
    const fromResolve: BrandColorOverrides =
      resolvedPrimary && !portalColors.primary ? { primary: resolvedPrimary } : {};
    const productDefaults = isCombinedApp() ? PRODUCT_COLOR_OVERRIDES : {};

    return {
      branding,
      schoolName: branding?.school_name?.trim() || resolvedName?.trim() || productFallbackName(),
      logoUrl: branding?.logo_url ?? resolvedLogo,
      loginBackgroundUrl: branding?.login_background_url ?? null,
      colorOverrides: {
        ...productDefaults,
        ...fromResolve,
        ...portalColors,
      },
      loading,
      refresh,
    };
  }, [branding, loading, refresh, resolvedLogo, resolvedName, resolvedPrimary]);

  return <BrandingContext.Provider value={value}>{children}</BrandingContext.Provider>;
};

export function useBranding(): BrandingContextValue {
  const ctx = useContext(BrandingContext);
  if (!ctx) {
    throw new Error('useBranding must be used within BrandingProvider');
  }
  return ctx;
}
