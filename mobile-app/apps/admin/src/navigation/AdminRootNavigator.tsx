import { canAccessApp, useAuth } from '@erp/core';
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
  PinEnableScreen,
} from '../features/auth';
import { DrawerNavigator } from './DrawerNavigator';
import { linking } from './linking';
import { OfflineShell } from '../providers/OfflineShell';

/**
 * Route guard (build plan §5.1). Resolves authentication + enrollment states.
 */
const RootGate: React.FC<{ navTheme: Theme }> = ({ navTheme }) => {
  const { status, user, biometricEnrollmentPending, pinEnrollmentPending } = useAuth();

  if (status === 'initializing') {
    return <AuthLoadingScreen />;
  }
  if (status === 'unauthenticated') {
    return <LoginScreen />;
  }
  if (!canAccessApp(user, 'admin')) {
    return <AccessDeniedScreen />;
  }
  if (biometricEnrollmentPending) {
    return <BiometricEnableScreen />;
  }
  if (pinEnrollmentPending) {
    return <PinEnableScreen />;
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

  return <RootGate navTheme={navTheme} />;
};
