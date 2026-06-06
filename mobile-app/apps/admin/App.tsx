import {
  AuthProvider,
  BiometricAuthProvider,
  getAppQueryClient,
  GoogleAuthProvider,
  RbacProvider,
  SessionProvider,
  useNetworkStatus,
} from '@erp/core';
import { AppErrorBoundary, OfflineBanner, useTheme } from '@erp/ui';
import { AppThemeProvider } from './src/providers/AppThemeProvider';
import { StatusBar } from 'expo-status-bar';
import React, { useEffect, useRef } from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { AdminRootNavigator } from './src/navigation/AdminRootNavigator';
import { AdminPushNotifications } from './src/providers/AdminPushNotifications';
import { PersistedQueryProvider } from './src/providers/PersistedQueryProvider';

const ThemedStatusBar: React.FC = () => {
  const { isDark } = useTheme();
  return <StatusBar style={isDark ? 'light' : 'dark'} />;
};

const OfflineShell: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const networkStatus = useNetworkStatus();
  const prevStatus = useRef(networkStatus);

  useEffect(() => {
    if (prevStatus.current !== 'online' && networkStatus === 'online') {
      void getAppQueryClient().invalidateQueries();
    }
    prevStatus.current = networkStatus;
  }, [networkStatus]);

  return (
    <>
      <OfflineBanner
        status={networkStatus}
        onRetry={() => void getAppQueryClient().refetchQueries({ type: 'active' })}
      />
      {children}
    </>
  );
};

export default function App(): React.JSX.Element {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <AppThemeProvider>
        <SafeAreaProvider>
          <AppErrorBoundary>
            <ThemedStatusBar />
            <SessionProvider>
              <AuthProvider>
                <PersistedQueryProvider>
                  <RbacProvider>
                    <GoogleAuthProvider>
                      <BiometricAuthProvider>
                        <OfflineShell>
                          <AdminPushNotifications />
                          <AdminRootNavigator />
                        </OfflineShell>
                      </BiometricAuthProvider>
                    </GoogleAuthProvider>
                  </RbacProvider>
                </PersistedQueryProvider>
              </AuthProvider>
            </SessionProvider>
          </AppErrorBoundary>
        </SafeAreaProvider>
      </AppThemeProvider>
    </GestureHandlerRootView>
  );
}
