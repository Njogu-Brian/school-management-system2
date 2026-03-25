import { useEffect, useRef } from 'react';
import { Platform } from 'react-native';
import * as Notifications from 'expo-notifications';
import * as Device from 'expo-device';
import Constants from 'expo-constants';
import { deviceApi } from '@api/device.api';

Notifications.setNotificationHandler({
    handleNotification: async () => ({
        shouldShowAlert: true,
        shouldPlaySound: false,
        shouldSetBadge: false,
    }),
});

/**
 * Registers for push notifications and saves the Expo push token to the API (for future server-side sends).
 */
export function usePushNotifications(enabled: boolean): void {
    const registered = useRef(false);

    useEffect(() => {
        if (!enabled || registered.current) {
            return;
        }

        let cancelled = false;

        (async () => {
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
                registered.current = true;
            } catch {
                /* Expo project not configured for push — ignore */
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [enabled]);
}
