import { useEffect, useRef } from 'react';
import { Platform } from 'react-native';
import Constants from 'expo-constants';
import { deviceApi } from '@api/device.api';
import AsyncStorage from '@react-native-async-storage/async-storage';

/** Remote push is not supported in Expo Go (SDK 53+). Use a dev build / production app for push tokens. */
const isExpoGo = Constants.executionEnvironment === 'storeClient';
const PUSH_TOKEN_STORAGE_KEY = '@expo_push_token';

/**
 * Registers for push notifications and saves the Expo push token to the API (for future server-side sends).
 * Skips all expo-notifications native work in Expo Go to avoid SDK 53+ errors and log noise.
 */
export function usePushNotifications(enabled: boolean): void {
    const registered = useRef(false);

    useEffect(() => {
        if (!enabled) {
            (async () => {
                try {
                    const token = await AsyncStorage.getItem(PUSH_TOKEN_STORAGE_KEY);
                    if (token) {
                        await deviceApi.revokePushToken(token);
                    }
                    await AsyncStorage.removeItem(PUSH_TOKEN_STORAGE_KEY);
                } catch {
                    /* ignore */
                } finally {
                    registered.current = false;
                }
            })();
            return;
        }

        if (!enabled || registered.current || isExpoGo) {
            return;
        }

        let cancelled = false;

        (async () => {
            const Notifications = await import('expo-notifications');
            const Device = await import('expo-device');

            Notifications.setNotificationHandler({
                handleNotification: async () => ({
                    shouldShowAlert: true,
                    shouldPlaySound: false,
                    shouldSetBadge: false,
                }),
            });

            if (!Device.isDevice) {
                return;
            }

            const { status: existing } = await Notifications.getPermissionsAsync();
            let finalStatus = existing;
            if (existing !== 'granted') {
                const { status } = await Notifications.requestPermissionsAsync();
                finalStatus = status;
            }
            if (finalStatus !== 'granted' || cancelled) {
                return;
            }

            try {
                const projectId =
                    Constants.expoConfig?.extra?.eas?.projectId ??
                    Constants.easConfig?.projectId ??
                    undefined;

                const tokenRes = await Notifications.getExpoPushTokenAsync(
                    projectId ? { projectId } : undefined
                );
                const token = tokenRes.data;
                if (!token || cancelled) {
                    return;
                }

                await deviceApi.registerPushToken(token, Platform.OS);
                await AsyncStorage.setItem(PUSH_TOKEN_STORAGE_KEY, token);
                registered.current = true;
            } catch {
                /* EAS project / FCM not configured — ignore */
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [enabled]);
}
