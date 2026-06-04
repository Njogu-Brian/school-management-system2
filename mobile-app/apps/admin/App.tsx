import {
  AuthProvider,
  BiometricAuthProvider,
  GoogleAuthProvider,
  RbacProvider,
  SessionProvider,
} from '@erp/core';
import { AppErrorBoundary, ThemeProvider, useTheme } from '@erp/ui';
import { StatusBar } from 'expo-status-bar';
import React from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { AdminRootNavigator } from './src/navigation/AdminRootNavigator';

/**
 * Admin App provider tree (build plan §4.2).
 *
 * GestureHandlerRootView → ThemeProvider → SafeAreaProvider → AppErrorBoundary →
 * SessionProvider → AuthProvider → RbacProvider → GoogleAuthProvider →
 * BiometricAuthProvider → AdminRootNavigator.
 *
 * Auth uses a strategy pattern (password / Google / biometric unlock). Google OAuth UI
 * lives in the app layer; biometric unlock only rehydrates an existing backend session.
 *
 * Deferred to later batches (require business logic, out of scope here):
 * QueryClientProvider, RbacProvider, ScopeProvider, NotificationPreferencesProvider.
 */
const ThemedStatusBar: React.FC = () => {
  const { isDark } = useTheme();
  return <StatusBar style={isDark ? 'light' : 'dark'} />;
};

export default function App(): React.JSX.Element {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <ThemeProvider>
        <SafeAreaProvider>
          <AppErrorBoundary>
            <ThemedStatusBar />
            <SessionProvider>
              <AuthProvider>
                <RbacProvider>
                  <GoogleAuthProvider>
                    <BiometricAuthProvider>
                      <AdminRootNavigator />
                    </BiometricAuthProvider>
                  </GoogleAuthProvider>
                </RbacProvider>
              </AuthProvider>
            </SessionProvider>
          </AppErrorBoundary>
        </SafeAreaProvider>
      </ThemeProvider>
    </GestureHandlerRootView>
  );
}
