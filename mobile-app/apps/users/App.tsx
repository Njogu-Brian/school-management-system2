import {
  AuthProvider,
  BiometricAuthProvider,
  RbacProvider,
  SessionProvider,
} from '@erp/core';
import { AppErrorBoundary, useTheme } from '@erp/ui';
import { StatusBar } from 'expo-status-bar';
import React from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { UsersRootNavigator } from './src/navigation/UsersRootNavigator';
import { AppThemeProvider } from './src/providers/AppThemeProvider';
import { PersistedQueryProvider } from './src/providers/PersistedQueryProvider';
import { UsersPushNotifications } from './src/providers/UsersPushNotifications';
import { useExpoOtaUpdates } from './src/hooks/useExpoOtaUpdates';

const ThemedStatusBar: React.FC = () => {
  const { isDark } = useTheme();
  return <StatusBar style={isDark ? 'light' : 'dark'} />;
};

function AppRoot(): React.JSX.Element {
  useExpoOtaUpdates();
  return (
    <>
      <ThemedStatusBar />
      <SessionProvider>
        <AuthProvider>
          <PersistedQueryProvider>
            <RbacProvider>
              <BiometricAuthProvider>
                <UsersPushNotifications />
                <UsersRootNavigator />
              </BiometricAuthProvider>
            </RbacProvider>
          </PersistedQueryProvider>
        </AuthProvider>
      </SessionProvider>
    </>
  );
}

export default function App(): React.JSX.Element {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <SafeAreaProvider>
        <AppThemeProvider>
          <AppErrorBoundary>
            <AppRoot />
          </AppErrorBoundary>
        </AppThemeProvider>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
