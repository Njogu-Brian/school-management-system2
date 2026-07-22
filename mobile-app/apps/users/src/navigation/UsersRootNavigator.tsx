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
import { OfflineShell } from '../providers/OfflineShell';
import { RoleBasedNavigator } from './RoleBasedNavigator';

const RootGate: React.FC<{ navTheme: Theme }> = ({ navTheme }) => {
  const { status, user, biometricEnrollmentPending, pinEnrollmentPending } = useAuth();

  if (status === 'initializing') {
    return <AuthLoadingScreen />;
  }
  if (status === 'unauthenticated') {
    return <LoginScreen />;
  }
  if (!canAccessApp(user, 'users')) {
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
      <NavigationContainer theme={navTheme}>
        <RoleBasedNavigator />
      </NavigationContainer>
    </OfflineShell>
  );
};

export const UsersRootNavigator: React.FC = () => {
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
