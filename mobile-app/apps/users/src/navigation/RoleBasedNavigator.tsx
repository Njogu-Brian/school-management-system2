import {
  effectiveRole,
  isAdminAppRole,
  useAppMode,
  useCurrentUser,
  userCanWork,
  UserRole,
} from '@erp/core';
import { EmptyState, ScreenContainer } from '@erp/ui';
import React from 'react';
import { DriverTabNavigator } from './driver/DriverTabNavigator';
import { OpenAdminAppScreen } from './OpenAdminAppScreen';
import { ParentTabNavigator } from './parent/ParentTabNavigator';
import { StudentTabNavigator } from './student/StudentTabNavigator';
import { TeacherNavigator } from './teacher/TeacherNavigator';

function isTeacherLikeRole(role: UserRole | null | undefined): boolean {
  return (
    role === UserRole.TEACHER ||
    role === UserRole.SENIOR_TEACHER ||
    role === UserRole.SUPERVISOR
  );
}

/**
 * Role-adaptive shell — one binary, tabs chosen by role at runtime.
 *
 * Dual-identity users can switch between Work and Home. Mode changes remount this
 * tree (via key) after cache invalidation in AppModeSwitch.
 *
 * Critical: when Work is selected, always mount the staff shell even if the API
 * primary role string is still "Parent" (Senior Teacher + Parent accounts).
 */
export const RoleBasedNavigator: React.FC = () => {
  const user = useCurrentUser();
  const role = effectiveRole(user);
  const { mode, canSwitch, ready } = useAppMode();
  const canWork = userCanWork(user);

  if (!ready) {
    return (
      <ScreenContainer edges={['top', 'bottom']}>
        <EmptyState title="Loading…" message="Preparing your workspace." icon="hourglass-outline" />
      </ScreenContainer>
    );
  }

  const shellKey = `mode-${mode}-role-${role ?? 'none'}-dual-${canSwitch ? 1 : 0}`;
  const isAdminDual = (role === UserRole.DIRECTOR || isAdminAppRole(role)) && (user?.parentId || user?.canHomeMode);

  let body: React.ReactElement;

  if (canSwitch && mode === 'home') {
    body = <ParentTabNavigator />;
  } else if (canSwitch && mode === 'work') {
    // Dual-identity Work: never remount Parent shell just because role resolves to Parent.
    if (isAdminDual) {
      body = <OpenAdminAppScreen />;
    } else if (role === UserRole.DRIVER || role === UserRole.TRANSPORT) {
      body = <DriverTabNavigator />;
    } else {
      body = <TeacherNavigator />;
    }
  } else if (isAdminDual) {
    body = mode === 'work' ? <OpenAdminAppScreen /> : <ParentTabNavigator />;
  } else if (isTeacherLikeRole(role)) {
    body = <TeacherNavigator />;
  } else if (
    // Staff linked but Spatie primary role still Parent — treat as teacher work shell.
    canWork &&
    (user?.staffId || user?.canWorkMode) &&
    (role === UserRole.PARENT || role === UserRole.GUARDIAN)
  ) {
    body = <TeacherNavigator />;
  } else if (role === UserRole.PARENT || role === UserRole.GUARDIAN) {
    body = <ParentTabNavigator />;
  } else if (role === UserRole.STUDENT) {
    body = <StudentTabNavigator />;
  } else if (role === UserRole.DRIVER || role === UserRole.TRANSPORT) {
    body = <DriverTabNavigator />;
  } else {
    body = (
      <ScreenContainer edges={['top', 'bottom']}>
        <EmptyState
          title="Unsupported role"
          message="Your account role is recognized for this app but has no dedicated shell yet. Contact your school administrator."
          icon="help-circle-outline"
        />
      </ScreenContainer>
    );
  }

  return <React.Fragment key={shellKey}>{body}</React.Fragment>;
};
