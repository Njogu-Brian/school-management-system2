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
import React from 'react';
import { DriverTabNavigator } from '@users/navigation/driver/DriverTabNavigator';
import { ParentTabNavigator } from '@users/navigation/parent/ParentTabNavigator';
import { StudentTabNavigator } from '@users/navigation/student/StudentTabNavigator';
import { TeacherNavigator } from '@users/navigation/teacher/TeacherNavigator';

/**
 * Combined iOS/iPadOS shell — every Admin and Users navigator, switched by role + Work|Home.
 *
 * Work mode
 *  - Admin / director / secretary / finance → full Admin drawer (dashboard, students,
 *    finance, HR, approvals, admissions, academics, operations, communication, reports, settings)
 *  - Teacher / senior teacher / supervisor → Teacher tabs
 *  - Driver / transport → Driver tabs
 *  - Parent / guardian (work not available) → Parent tabs
 *  - Student → Student tabs
 *
 * Home mode (dual-identity staff/admins who also have a parent profile)
 *  - Full Parent tabs (children, fees, academics, wallet, diary, transport, …)
 */
export const CombinedRoleNavigator: React.FC = () => {
  const user = useCurrentUser();
  const role = effectiveRole(user);
  const { mode } = useAppMode();
  const canHome = userCanHome(user);
  const canWork = userCanWork(user);

  if (canHome && (mode === 'home' || !canWork)) {
    return <ParentTabNavigator />;
  }

  if (isAdminAppRole(role) || role === UserRole.DIRECTOR) {
    return (
      <ScreenContainerDefaultsProvider edges={['bottom']}>
        <DrawerNavigator />
      </ScreenContainerDefaultsProvider>
    );
  }

  if (
    role === UserRole.TEACHER ||
    role === UserRole.SENIOR_TEACHER ||
    role === UserRole.SUPERVISOR
  ) {
    return <TeacherNavigator />;
  }

  if (role === UserRole.PARENT || role === UserRole.GUARDIAN) {
    return <ParentTabNavigator />;
  }

  if (role === UserRole.STUDENT) {
    return <StudentTabNavigator />;
  }

  if (role === UserRole.DRIVER || role === UserRole.TRANSPORT) {
    return <DriverTabNavigator />;
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
