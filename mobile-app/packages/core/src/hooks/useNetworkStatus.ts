import { useEffect, useState } from 'react';
import { NativeModules, TurboModuleRegistry } from 'react-native';

export type NetworkStatus = 'online' | 'offline' | 'reconnecting';

function hasNetInfoNativeModule(): boolean {
  try {
    const turbo = TurboModuleRegistry.get?.('RNCNetInfo');
    return Boolean(turbo || NativeModules.RNCNetInfo);
  } catch {
    return false;
  }
}

/**
 * Network connectivity for offline UX. Never throws on missing native module
 * (Play/EAS builds must still boot if NetInfo failed to autolink).
 */
export function useNetworkStatus(): NetworkStatus {
  const [status, setStatus] = useState<NetworkStatus>('online');

  useEffect(() => {
    let cancelled = false;
    let unsubscribe: (() => void) | undefined;

    void (async () => {
      if (!hasNetInfoNativeModule()) {
        return;
      }
      try {
        const NetInfo = await import('@react-native-community/netinfo');
        if (cancelled) return;

        let wasOffline = false;
        const update = (state: Awaited<ReturnType<typeof NetInfo.default.fetch>>) => {
          const online = state.isConnected === true && state.isInternetReachable !== false;
          if (online) {
            setStatus(wasOffline ? 'reconnecting' : 'online');
            if (wasOffline) {
              setTimeout(() => setStatus('online'), 2000);
            }
            wasOffline = false;
          } else {
            wasOffline = true;
            setStatus('offline');
          }
        };

        unsubscribe = NetInfo.default.addEventListener(update);
        void NetInfo.default.fetch().then(update);
      } catch {
        if (!cancelled) {
          setStatus('online');
        }
      }
    })();

    return () => {
      cancelled = true;
      unsubscribe?.();
    };
  }, []);

  return status;
}
