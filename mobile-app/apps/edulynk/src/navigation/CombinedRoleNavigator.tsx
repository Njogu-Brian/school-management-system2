import {
  effectiveRole,
  isAdminAppRole,
  useAppMode,
  useCurrentUser,
  userCanHome,
  userCanWork,
  UserRole,
} from '@erp/core';
import { EmptyState, ScreenContainer, ScreenContainerDefaultsProvider } from '@erp/ui';
import { DrawerNavigator } from '@admin/navigation/DrawerNavigator';
import { AuthLoadingScreen } from '@users/features/auth';
import React from 'react';
import { DriverTabNavigator } from '@users/navigation/driver/DriverTabNavigator';
import { ParentTabNavigator } from '@users/navigation/parent/ParentTabNavigator';
import { StudentTabNavigator } from '@users/navigation/student/StudentTabNavigator';
import { TeacherNavigator } from '@users/navigation/teacher/TeacherNavigator';

/**
 * Combined Edulynk shell — Admin and Users navigators, switched by role + Work|Home.
 *
 * Work mode must mount the staff shell for Senior Teacher + Parent dual accounts
 * even when the API primary role string is still "Parent".
 */
export const CombinedRoleNavigator: React.FC = () => {
  const user = useCurrentUser();
  const role = effectiveRole(user);
  const { mode, ready } = useAppMode();
  const canHome = userCanHome(user);
  const canWork = userCanWork(user);
  const adminWork = isAdminAppRole(role) || role === UserRole.DIRECTOR;

  if (!ready) {
    return <AuthLoadingScreen />;
  }

  // Parent tabs only when the user explicitly chose Home.
  if (canHome && mode === 'home' && !adminWork) {
    return <ParentTabNavigator />;
  }

  if (adminWork) {
    if (canHome && mode === 'home') {
      return <ParentTabNavigator />;
    }
    return (
      <ScreenContainerDefaultsProvider edges={['bottom']}>
        <DrawerNavigator />
      </ScreenContainerDefaultsProvider>
    );
  }

  if (role === UserRole.DRIVER || role === UserRole.TRANSPORT) {
    return <DriverTabNavigator />;
  }

  if (role === UserRole.STUDENT) {
    return <StudentTabNavigator />;
  }

  // Teacher / senior / supervisor, or dual Parent+staff in Work mode.
  if (
    role === UserRole.TEACHER ||
    role === UserRole.SENIOR_TEACHER ||
    role === UserRole.SUPERVISOR ||
    (mode === 'work' &&
      canWork &&
      (Boolean(user?.staffId) || Boolean(user?.canWorkMode)) &&
      (role === UserRole.PARENT || role === UserRole.GUARDIAN || role == null))
  ) {
    return <TeacherNavigator />;
  }

  if (role === UserRole.PARENT || role === UserRole.GUARDIAN) {
    return <ParentTabNavigator />;
  }

  return (
    <ScreenContainer edges={['top', 'bottom']}>
      <EmptyState
        title="Unsupported role"
        message="Your account role is recognized but has no dedicated shell yet. Contact your school administrator."
        icon="help-circle-outline"
      />
    </ScreenContainer>
  );
};
