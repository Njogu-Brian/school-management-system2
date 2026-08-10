import {
  appIssuesApi,
  AuthProvider,
  BiometricAuthProvider,
  RbacProvider,
  SessionProvider,
} from '@erp/core';
import { AppErrorBoundary, registerAppIssueReporter, ScreenContainerDefaultsProvider, useTheme } from '@erp/ui';
import { AppThemeProvider } from './src/providers/AppThemeProvider';
import Constants from 'expo-constants';
import { StatusBar } from 'expo-status-bar';
import React, { useEffect } from 'react';
import { Platform } from 'react-native';
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
  useEffect(() => {
    registerAppIssueReporter(async (payload) => {
      await appIssuesApi.report({
        ...payload,
        app: 'admin',
        platform: payload.platform ?? Platform.OS,
        app_version: Constants.expoConfig?.version ?? Constants.nativeAppVersion ?? undefined,
      });
    });
    return () => registerAppIssueReporter(null);
  }, []);

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <SafeAreaProvider>
        <AppThemeProvider>
          <AppErrorBoundary appName="admin">
            <ThemedStatusBar />
            <SessionProvider>
              <AuthProvider>
                <PersistedQueryProvider>
                  <RbacProvider>
                    <BiometricAuthProvider>
                      <AdminPushNotifications />
                      <ScreenContainerDefaultsProvider edges={['bottom']}>
                        <AdminRootNavigator />
                      </ScreenContainerDefaultsProvider>
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
