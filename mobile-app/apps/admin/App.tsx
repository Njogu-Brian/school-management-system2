import {
  AuthProvider,
  BiometricAuthProvider,
  RbacProvider,
  SessionProvider,
} from '@erp/core';
import { AppErrorBoundary, useTheme } from '@erp/ui';
import { AppThemeProvider } from './src/providers/AppThemeProvider';
import { StatusBar } from 'expo-status-bar';
import React from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { AdminRootNavigator } from './src/navigation/AdminRootNavigator';
import { AdminPushNotifications } from './src/providers/AdminPushNotifications';
import { PersistedQueryProvider } from './src/providers/PersistedQueryProvider';

const ThemedStatusBar: React.FC = () => {
  const { isDark } = useTheme();
  return <StatusBar style={isDark ? 'light' : 'dark'} />;
};

export default function App(): React.JSX.Element {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <SafeAreaProvider>
        <AppThemeProvider>
          <AppErrorBoundary>
            <ThemedStatusBar />
            <SessionProvider>
              <AuthProvider>
                <PersistedQueryProvider>
                  <RbacProvider>
                    <BiometricAuthProvider>
                      <AdminPushNotifications />
                      <AdminRootNavigator />
                    </BiometricAuthProvider>
                  </RbacProvider>
                </PersistedQueryProvider>
              </AuthProvider>
            </SessionProvider>
          </AppErrorBoundary>
        </AppThemeProvider>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
