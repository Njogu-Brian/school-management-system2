import {
  BrandingProvider,
  getSurfaceMode,
  getThemeMode,
  setSurfaceMode,
  setThemeMode,
  useBranding,
  type SurfaceMode,
  type ThemeMode,
} from '@erp/core';
import { ThemeProvider, ToastProvider } from '@erp/ui';
import React, { useCallback, useEffect, useState } from 'react';
import { FeedbackProvider } from './FeedbackProvider';

const ThemedShell: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { colorOverrides } = useBranding();
  const [themeMode, setMode] = useState<ThemeMode>('auto');
  const [surfaceMode, setSurface] = useState<SurfaceMode>('default');

  useEffect(() => {
    void getThemeMode().then(setMode);
    void getSurfaceMode().then(setSurface);
  }, []);

  const onThemeModeChange = useCallback((mode: ThemeMode) => {
    setMode(mode);
    void setThemeMode(mode);
  }, []);

  const onSurfaceModeChange = useCallback((mode: SurfaceMode) => {
    setSurface(mode);
    void setSurfaceMode(mode);
  }, []);

  return (
    <ThemeProvider
      themeMode={themeMode}
      onThemeModeChange={onThemeModeChange}
      surfaceMode={surfaceMode}
      colorOverrides={colorOverrides}
    >
      <SurfaceModeBridge surfaceMode={surfaceMode} onSurfaceModeChange={onSurfaceModeChange}>
        <ToastProvider>
          <FeedbackProvider>{children}</FeedbackProvider>
        </ToastProvider>
      </SurfaceModeBridge>
    </ThemeProvider>
  );
};

const SurfaceModeContext = React.createContext<{
  surfaceMode: SurfaceMode;
  setSurfaceMode: (mode: SurfaceMode) => void;
}>({
  surfaceMode: 'default',
  setSurfaceMode: () => undefined,
});

export function useSurfaceModeControl() {
  return React.useContext(SurfaceModeContext);
}

const SurfaceModeBridge: React.FC<{
  children: React.ReactNode;
  surfaceMode: SurfaceMode;
  onSurfaceModeChange: (mode: SurfaceMode) => void;
}> = ({ children, surfaceMode, onSurfaceModeChange }) => (
  <SurfaceModeContext.Provider value={{ surfaceMode, setSurfaceMode: onSurfaceModeChange }}>
    {children}
  </SurfaceModeContext.Provider>
);

export const AppThemeProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => (
  <BrandingProvider>
    <ThemedShell>{children}</ThemedShell>
  </BrandingProvider>
);
