import {
  AppModeProvider,
  canAccessApp,
  effectiveRole,
  isAdminAppRole,
  useAuth,
  UserRole,
} from '@erp/core';
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
  ForceChangePasswordScreen,
  LoginScreen,
  ParentProfileReviewScreen,
} from '../features/auth';
import { OfflineShell } from '../providers/OfflineShell';
import { RoleBasedNavigator } from './RoleBasedNavigator';

const RootGate: React.FC<{ navTheme: Theme }> = ({ navTheme }) => {
  const { status, user, biometricEnrollmentPending, forcePasswordChangePending } = useAuth();

  if (status === 'initializing') {
    return <AuthLoadingScreen />;
  }
  if (status === 'unauthenticated') {
    return <LoginScreen />;
  }
  if (!canAccessApp(user, 'users')) {
    return <AccessDeniedScreen />;
  }
  // Director signed in but no child linked yet → link-child CTA (same credentials).
  const role = effectiveRole(user);
  if (
    (role === UserRole.DIRECTOR || isAdminAppRole(role)) &&
    !user?.parentId &&
    !user?.canHomeMode
  ) {
    return <AccessDeniedScreen />;
  }
  // Only after admin-triggered flag + a fresh sign-in (not session restore).
  if (forcePasswordChangePending) {
    return <ForceChangePasswordScreen />;
  }
  if (biometricEnrollmentPending) {
    return <BiometricEnableScreen />;
  }
  // Freshly claimed parent accounts must review their family details first (data only).
  if (user?.parentProfileReviewRequired) {
    return <ParentProfileReviewScreen />;
  }
  return (
    <OfflineShell>
      <AppModeProvider>
        <NavigationContainer theme={navTheme}>
          <RoleBasedNavigator />
        </NavigationContainer>
      </AppModeProvider>
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
