import React, { createContext, useState, useContext, ReactNode, useEffect, useMemo } from 'react';
import { useColorScheme } from 'react-native';
import { COLORS } from '@constants/theme';
import { brandingApi } from '@api/branding.api';
import { mergePortalColors } from '@utils/mergePortalColors';

type ThemeMode = 'light' | 'dark' | 'auto';

interface ThemeContextType {
    mode: ThemeMode;
    isDark: boolean;
    colors: typeof COLORS;
    setThemeMode: (mode: ThemeMode) => void;
    toggleTheme: () => void;
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

interface ThemeProviderProps {
    children: ReactNode;
}

export const ThemeProvider: React.FC<ThemeProviderProps> = ({ children }) => {
    const systemColorScheme = useColorScheme();
    const [mode, setMode] = useState<ThemeMode>('light');
    const [portalColors, setPortalColors] = useState<typeof COLORS | null>(null);

    const isDark = mode === 'dark' || (mode === 'auto' && systemColorScheme === 'dark');

    useEffect(() => {
        let cancelled = false;
        brandingApi
            .getBranding()
            .then((b) => {
                if (!cancelled && b.colors) {
                    setPortalColors(mergePortalColors(b.colors));
                }
            })
            .catch(() => {
                /* keep defaults */
            });
        return () => {
            cancelled = true;
        };
    }, []);

    const mergedColors = useMemo(() => {
        if (!portalColors) {
            return COLORS;
        }
        return { ...COLORS, ...portalColors };
    }, [portalColors]);

    const setThemeMode = (newMode: ThemeMode) => {
        setMode(newMode);
    };

    const toggleTheme = () => {
        setMode((prev) => (prev === 'light' ? 'dark' : 'light'));
    };

    const value: ThemeContextType = {
        mode,
        isDark,
        colors: mergedColors,
        setThemeMode,
        toggleTheme,
    };

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
};

export const useTheme = (): ThemeContextType => {
    const context = useContext(ThemeContext);
    if (!context) {
        throw new Error('useTheme must be used within ThemeProvider');
    }
    return context;
};
