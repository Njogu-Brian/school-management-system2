import { useEffect, useState } from 'react';
import NetInfo, { NetInfoState } from '@react-native-community/netinfo';

export function useNetworkStatus(): boolean {
    const [online, setOnline] = useState(true);

    useEffect(() => {
        const sub = NetInfo.addEventListener((state: NetInfoState) => {
            setOnline(state.isConnected === true && state.isInternetReachable !== false);
        });
        NetInfo.fetch().then((state) => {
            setOnline(state.isConnected === true && state.isInternetReachable !== false);
        });
        return () => sub();
    }, []);

    return online;
}
