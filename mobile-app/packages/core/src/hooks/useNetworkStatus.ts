import { useEffect, useState } from 'react';
import NetInfo, { type NetInfoState } from '@react-native-community/netinfo';

export type NetworkStatus = 'online' | 'offline' | 'reconnecting';

export function useNetworkStatus(): NetworkStatus {
  const [status, setStatus] = useState<NetworkStatus>('online');

  useEffect(() => {
    let wasOffline = false;
    const update = (state: NetInfoState) => {
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
    const sub = NetInfo.addEventListener(update);
    void NetInfo.fetch().then(update);
    return () => sub();
  }, []);

  return status;
}
