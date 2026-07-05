import {
  AuthProvider,
  BiometricAuthProvider,
  getAppQueryClient,
  RbacProvider,
  SessionProvider,
  useAuth,
  useNetworkStatus,
  useSession,
} from '@erp/core';
import { AppErrorBoundary, OfflineBanner, useTheme } from '@erp/ui';
import { AppThemeProvider } from './src/providers/AppThemeProvider';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import React, { useEffect, useRef } from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { AdminRootNavigator } from './src/navigation/AdminRootNavigator';
import { AdminPushNotifications } from './src/providers/AdminPushNotifications';
import { PersistedQueryProvider } from './src/providers/PersistedQueryProvider';

void SplashScreen.preventAutoHideAsync().catch(() => undefined);

const SplashReadyGate: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { hydrated } = useSession();
  const { status } = useAuth();

  useEffect(() => {
    if (hydrated && status !== 'initializing') {
      void SplashScreen.hideAsync();
    }
  }, [hydrated, status]);

  return <>{children}</>;
};

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
                <SplashReadyGate>
                  <PersistedQueryProvider>
                    <RbacProvider>
                      <BiometricAuthProvider>
                        <OfflineShell>
                          <AdminPushNotifications />
                          <AdminRootNavigator />
                        </OfflineShell>
                      </BiometricAuthProvider>
                    </RbacProvider>
                  </PersistedQueryProvider>
                </SplashReadyGate>
              </AuthProvider>
            </SessionProvider>
          </AppErrorBoundary>
        </SafeAreaProvider>
      </AppThemeProvider>
    </GestureHandlerRootView>
  );
}
