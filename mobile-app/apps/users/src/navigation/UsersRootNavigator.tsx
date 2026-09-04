import {
  AppModeProvider,
  canAccessApp,
  effectiveRole,
  isAdminAppRole,
  useAuth,
  useSchool,
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
  // ForceChangePasswordScreen — kept in features/auth; re-enable gate below when ready
  LoginScreen,
  PinEnableScreen,
  SchoolCodeScreen,
} from '../features/auth';
import { OfflineShell } from '../providers/OfflineShell';
import { RoleBasedNavigator } from './RoleBasedNavigator';

const RootGate: React.FC<{ navTheme: Theme }> = ({ navTheme }) => {
  const {
    status,
    user,
    biometricEnrollmentPending,
    pinEnrollmentPending,
    // forcePasswordChangePending — kept for when ForceChangePasswordScreen is re-enabled
  } = useAuth();
  const school = useSchool();

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
  // Temp credentials / admin reset — force password change before continuing.
  // Hidden for now (password fields flicker on Android). Screen still exists under
  // features/auth/ForceChangePasswordScreen. When re-enabling: uncomment below and
  // set must_change_password=true for the accounts that must change on next login.
  // if (forcePasswordChangePending || user?.mustChangePassword) {
  //   return <ForceChangePasswordScreen />;
  // }
  if (biometricEnrollmentPending) {
    return <BiometricEnableScreen />;
  }
  // After first password login (or skipping biometrics), offer a digit PIN unlock.
  // PIN keypad has no soft keyboard — avoids the password-field flicker on Android.
  if (pinEnrollmentPending) {
    return <PinEnableScreen />;
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
