import * as Updates from 'expo-updates';
import { useEffect } from 'react';
import { AppState, type AppStateStatus } from 'react-native';

/**
 * Checks for OTA updates when the app loads / returns to foreground.
 * Requires a native build that was compiled with updates.enabled=true.
 */
export function useExpoOtaUpdates(): void {
  useEffect(() => {
    if (__DEV__ || !Updates.isEnabled) {
      return;
    }

    let cancelled = false;

    const check = async () => {
      try {
        const result = await Updates.checkForUpdateAsync();
        if (cancelled || !result.isAvailable) {
          return;
        }
        await Updates.fetchUpdateAsync();
        if (!cancelled) {
          await Updates.reloadAsync();
        }
      } catch {
        // Network / channel errors should not block the app.
      }
    };

    void check();

    const onChange = (state: AppStateStatus) => {
      if (state === 'active') {
        void check();
      }
    };
    const sub = AppState.addEventListener('change', onChange);
    return () => {
      cancelled = true;
      sub.remove();
    };
  }, []);
}
