import {
  appIssuesApi,
  AuthProvider,
  BiometricAuthProvider,
  RbacProvider,
  SessionProvider,
} from '@erp/core';
import { AppErrorBoundary, registerAppIssueReporter, useTheme } from '@erp/ui';
import Constants from 'expo-constants';
import { StatusBar } from 'expo-status-bar';
import React, { useEffect } from 'react';
import { Platform } from 'react-native';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { UsersRootNavigator } from './src/navigation/UsersRootNavigator';
import { AppThemeProvider } from './src/providers/AppThemeProvider';
import { PersistedQueryProvider } from './src/providers/PersistedQueryProvider';
import { UsersPushNotifications } from './src/providers/UsersPushNotifications';
import { useExpoOtaUpdates } from './src/hooks/useExpoOtaUpdates';

const ThemedStatusBar: React.FC = () => {
  const { isDark } = useTheme();
  return <StatusBar style={isDark ? 'light' : 'dark'} translucent />;
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
  useEffect(() => {
    registerAppIssueReporter(async (payload) => {
      await appIssuesApi.report({
        ...payload,
        app: 'users',
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
          <AppErrorBoundary appName="users">
            <AppRoot />
          </AppErrorBoundary>
        </AppThemeProvider>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
