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
} from '../features/auth';
import { DrawerNavigator } from './DrawerNavigator';
import { linking } from './linking';

/**
 * Route guard (build plan §5.1). Resolves the four authentication states:
 *   initializing            → splash while the session is restored
 *   unauthenticated         → Login
 *   authenticated, wrong app→ Access Denied (e.g. a Teacher in the Admin Console)
 *   authenticated, enrollment → biometric enable prompt (first login, skippable)
 *   authenticated, allowed    → the app (drawer shell)
 *
 * Login / loading / denied render outside NavigationContainer (they need no navigator),
 * so deep links only resolve once the user is inside the app.
 */
const RootGate: React.FC<{ navTheme: Theme }> = ({ navTheme }) => {
  const { status, user, biometricEnrollmentPending } = useAuth();

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
  return (
    <NavigationContainer theme={navTheme} linking={linking}>
      <DrawerNavigator />
    </NavigationContainer>
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
