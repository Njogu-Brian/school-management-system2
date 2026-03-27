import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {
    DEFAULT_NOTIFICATION_PREFERENCES,
    NotificationPreferences,
    notificationPreferencesApi,
} from '@api/notificationPreferences.api';

const STORAGE_KEY = '@notification_preferences_v1';

interface NotificationPreferencesContextType {
    preferences: NotificationPreferences;
    loading: boolean;
    updatePreferences: (next: NotificationPreferences) => Promise<void>;
}

const NotificationPreferencesContext = createContext<NotificationPreferencesContextType | undefined>(undefined);

export const NotificationPreferencesProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [preferences, setPreferences] = useState<NotificationPreferences>(DEFAULT_NOTIFICATION_PREFERENCES);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let mounted = true;

        (async () => {
            try {
                const localRaw = await AsyncStorage.getItem(STORAGE_KEY);
                if (localRaw && mounted) {
                    const parsed = JSON.parse(localRaw) as NotificationPreferences;
                    setPreferences({ ...DEFAULT_NOTIFICATION_PREFERENCES, ...parsed });
                }

                const remote = await notificationPreferencesApi.getPreferences();
                if (mounted && remote.success && remote.data) {
                    const merged = { ...DEFAULT_NOTIFICATION_PREFERENCES, ...remote.data };
                    setPreferences(merged);
                    await AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(merged));
                }
            } catch {
                // Keep local defaults if remote fetch fails.
            } finally {
                if (mounted) setLoading(false);
            }
        })();

        return () => {
            mounted = false;
        };
    }, []);

    const updatePreferences = useCallback(async (next: NotificationPreferences) => {
        setPreferences(next);
        await AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(next));
        try {
            await notificationPreferencesApi.updatePreferences(next);
        } catch {
            // Keep local state and retry on next app session.
        }
    }, []);

    const value = useMemo(
        () => ({
            preferences,
            loading,
            updatePreferences,
        }),
        [preferences, loading, updatePreferences]
    );

    return (
        <NotificationPreferencesContext.Provider value={value}>
            {children}
        </NotificationPreferencesContext.Provider>
    );
};

export function useNotificationPreferences(): NotificationPreferencesContextType {
    const context = useContext(NotificationPreferencesContext);
    if (!context) {
        throw new Error('useNotificationPreferences must be used within NotificationPreferencesProvider');
    }
    return context;
}
