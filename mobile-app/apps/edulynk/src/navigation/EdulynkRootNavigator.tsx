import {
  AppModeProvider,
  canAccessApp,
  useAppMode,
  useAuth,
  useSchool,
} from '@erp/core';
import { EmptyState, ScreenContainer, useTheme } from '@erp/ui';
import {
  AuthLoadingScreen,
  BiometricEnableScreen,
  ForceChangePasswordScreen,
  LoginScreen,
  PinEnableScreen,
  SchoolCodeScreen,
} from '@users/features/auth';
import {
  DarkTheme,
  DefaultTheme,
  NavigationContainer,
  Theme,
} from '@react-navigation/native';
import React, { useMemo } from 'react';
import { CombinedRoleNavigator } from './CombinedRoleNavigator';
import { OfflineShell } from '../providers/OfflineShell';

const UnrecognizedRoleScreen: React.FC = () => {
  const { logout } = useAuth();
  return (
    <ScreenContainer edges={['top', 'bottom']}>
      <EmptyState
        title="Access denied"
        message="Your account role is not recognized. Sign in with a different account or contact your school administrator."
        icon="lock-closed-outline"
        actionLabel="Sign in with a different account"
        onAction={logout}
      />
    </ScreenContainer>
  );
};

const RootGate: React.FC<{ navTheme: Theme }> = ({ navTheme }) => {
  const {
    status,
    user,
    biometricEnrollmentPending,
    pinEnrollmentPending,
    forcePasswordChangePending,
  } = useAuth();
  const school = useSchool();
  const { ready } = useAppMode();

  if (school.status === 'initializing') {
    return <AuthLoadingScreen />;
  }
  if (school.status === 'needs_code') {
    return <SchoolCodeScreen />;
  }
  if (status === 'initializing') {
    return <AuthLoadingScreen />;
  }
  if (status === 'unauthenticated') {
    return <LoginScreen />;
  }
  if (!canAccessApp(user, 'combined')) {
    return <UnrecognizedRoleScreen />;
  }
  if (forcePasswordChangePending || user?.mustChangePassword) {
    return <ForceChangePasswordScreen />;
  }
  if (biometricEnrollmentPending) {
    return <BiometricEnableScreen />;
  }
  if (pinEnrollmentPending) {
    return <PinEnableScreen />;
  }
  if (!ready) {
    return <AuthLoadingScreen />;
  }
  return (
    <OfflineShell>
      <NavigationContainer theme={navTheme}>
        <CombinedRoleNavigator />
      </NavigationContainer>
    </OfflineShell>
  );
};

export const EdulynkRootNavigator: React.FC = () => {
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
