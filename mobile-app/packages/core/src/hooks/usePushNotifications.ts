import { useEffect, useRef } from 'react';
import { Platform } from 'react-native';
import Constants from 'expo-constants';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { deviceApi } from '../api/device.api';

const isExpoGo = Constants.executionEnvironment === 'storeClient';
const PUSH_TOKEN_STORAGE_KEY = '@erp_expo_push_token';

export type ForegroundPushPayload = {
  title: string;
  body: string;
};

/**
 * Registers Expo push tokens with the API and shows OS banners (with sound) in the foreground.
 */
export function usePushNotifications(
  enabled: boolean,
  onForeground?: (payload: ForegroundPushPayload) => void,
): void {
  const registered = useRef(false);
  const onForegroundRef = useRef(onForeground);
  onForegroundRef.current = onForeground;

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

    let cancelled = false;
    let receivedSub: { remove: () => void } | undefined;

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

      if (Platform.OS === 'android') {
        await Notifications.setNotificationChannelAsync('parent-alerts', {
          name: 'School alerts',
          importance: Notifications.AndroidImportance.HIGH,
          sound: 'default',
          vibrationPattern: [0, 250, 250, 250],
          enableVibrate: true,
          showBadge: true,
        });
      }

      receivedSub = Notifications.addNotificationReceivedListener((event) => {
        const content = event.request.content;
        const title = content.title ?? 'School alert';
        const body = content.body ?? '';
        onForegroundRef.current?.({ title, body });
      });

      if (!Device.isDevice || cancelled) {
        return;
      }

      const { status: existing } = await Notifications.getPermissionsAsync();
      let finalStatus = existing;
      if (existing !== 'granted') {
        const { status } = await Notifications.requestPermissionsAsync();
        finalStatus = status;
      }
      if (finalStatus !== 'granted' || cancelled || registered.current || isExpoGo) {
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
      receivedSub?.remove();
    };
  }, [enabled]);
}
