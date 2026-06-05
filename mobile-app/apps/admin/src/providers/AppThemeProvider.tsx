import { BrandingProvider, getThemeMode, setThemeMode, useBranding, type ThemeMode } from '@erp/core';
import { ThemeProvider } from '@erp/ui';
import React, { useCallback, useEffect, useState } from 'react';

const ThemedShell: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { colorOverrides } = useBranding();
  const [themeMode, setMode] = useState<ThemeMode>('auto');

  useEffect(() => {
    void getThemeMode().then(setMode);
  }, []);

  const onThemeModeChange = useCallback((mode: ThemeMode) => {
    setMode(mode);
    void setThemeMode(mode);
  }, []);

  return (
    <ThemeProvider
      themeMode={themeMode}
      onThemeModeChange={onThemeModeChange}
      colorOverrides={colorOverrides}
    >
      {children}
    </ThemeProvider>
  );
};

export const AppThemeProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => (
  <BrandingProvider>
    <ThemedShell>{children}</ThemedShell>
  </BrandingProvider>
);
