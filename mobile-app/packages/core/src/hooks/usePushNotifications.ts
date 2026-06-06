import { useEffect, useRef } from 'react';
import { Platform } from 'react-native';
import Constants from 'expo-constants';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { deviceApi } from '../api/device.api';

const isExpoGo = Constants.executionEnvironment === 'storeClient';
const PUSH_TOKEN_STORAGE_KEY = '@erp_expo_push_token';

/**
 * Registers Expo push tokens with the API for server-side alerts.
 */
export function usePushNotifications(enabled: boolean): void {
  const registered = useRef(false);

  useEffect(() => {
    if (!enabled) {
      void (async () => {
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

    if (registered.current || isExpoGo) {
      return;
    }

    let cancelled = false;

    void (async () => {
      const Notifications = await import('expo-notifications');
      const Device = await import('expo-device');

      Notifications.setNotificationHandler({
        handleNotification: async () => ({
          shouldShowAlert: true,
          shouldShowBanner: true,
          shouldShowList: true,
          shouldPlaySound: true,
          shouldSetBadge: true,
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
          Constants.expoConfig?.extra?.eas?.projectId ?? Constants.easConfig?.projectId ?? undefined;

        const tokenRes = await Notifications.getExpoPushTokenAsync(projectId ? { projectId } : undefined);
        const token = tokenRes.data;
        if (!token || cancelled) {
          return;
        }

        await deviceApi.registerPushToken(token, Platform.OS);
        await AsyncStorage.setItem(PUSH_TOKEN_STORAGE_KEY, token);
        registered.current = true;
      } catch {
        /* EAS project / FCM not configured */
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [enabled]);
}
