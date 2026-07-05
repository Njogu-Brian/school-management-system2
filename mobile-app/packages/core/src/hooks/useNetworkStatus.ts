import { useEffect, useState } from 'react';

export type NetworkStatus = 'online' | 'offline' | 'reconnecting';

export function useNetworkStatus(): NetworkStatus {
  const [status, setStatus] = useState<NetworkStatus>('online');

  useEffect(() => {
    let cancelled = false;
    let unsubscribe: (() => void) | undefined;

    void (async () => {
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
