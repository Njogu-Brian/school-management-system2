import { AppModeProvider, canAccessApp, useAppMode, useAuth } from '@erp/core';
import { useTheme } from '@erp/ui';
import {
  DarkTheme,
  DefaultTheme,
  NavigationContainer,
  Theme,
} from '@react-navigation/native';
import React, { useMemo } from 'react';
import {
  AccessDeniedScreen,
  AuthLoadingScreen,
  BiometricEnableScreen,
  LoginScreen,
} from '../features/auth';
import { AdminParentHomeScreen } from '../features/parent/screens/AdminParentHomeScreen';
import { DrawerNavigator } from './DrawerNavigator';
import { linking } from './linking';
import { OfflineShell } from '../providers/OfflineShell';

/**
 * Route guard (build plan §5.1). Resolves authentication + enrollment states.
 */
const RootGate: React.FC<{ navTheme: Theme }> = ({ navTheme }) => {
  const { status, user, biometricEnrollmentPending } = useAuth();
  const { mode } = useAppMode();

  if (status === 'initializing') {
    return <AuthLoadingScreen />;
  }
  if (status === 'unauthenticated') {
    return <LoginScreen />;
  }
  if (!canAccessApp(user, 'admin')) {
    return <AccessDeniedScreen />;
  }
  // Force-password gate disabled — admin can still set must_change_password via staff/parent tools.
  if (biometricEnrollmentPending) {
    return <BiometricEnableScreen />;
  }
  // Admins who also hold a parent record can flip to a thin "Home" parent shell.
  if (user?.parentId && mode === 'home') {
    return (
      <OfflineShell>
        <AdminParentHomeScreen />
      </OfflineShell>
    );
  }
  return (
    <OfflineShell>
      <NavigationContainer theme={navTheme} linking={linking}>
        <DrawerNavigator />
      </NavigationContainer>
    </OfflineShell>
  );
};

export const AdminRootNavigator: React.FC = () => {
  const { isDark, palette, colors } = useTheme();

  const navTheme = useMemo<Theme>(() => {
    const base = isDark ? DarkTheme : DefaultTheme;
    return {
      ...base,
      colors: {
        ...base.colors,
        primary: colors.primary,
        background: palette.background,
        card: palette.surface,
        text: palette.textPrimary,
        border: palette.border,
      },
    };
  }, [isDark, palette, colors]);

  return (
    <AppModeProvider>
      <RootGate navTheme={navTheme} />
    </AppModeProvider>
  );
};
