import {
  effectiveRole,
  isAdminAppRole,
  useAppMode,
  useCurrentUser,
  UserRole,
} from '@erp/core';
import { EmptyState, ScreenContainer } from '@erp/ui';
import React from 'react';
import { DriverTabNavigator } from './driver/DriverTabNavigator';
import { OpenAdminAppScreen } from './OpenAdminAppScreen';
import { ParentTabNavigator } from './parent/ParentTabNavigator';
import { StudentTabNavigator } from './student/StudentTabNavigator';
import { TeacherNavigator } from './teacher/TeacherNavigator';

/**
 * Role-adaptive shell — one binary, tabs chosen by role at runtime.
 *
 * Dual-identity users can switch between Work and Home. Mode changes remount this
 * tree (via key) after cache invalidation in AppModeSwitch.
 */
export const RoleBasedNavigator: React.FC = () => {
  const user = useCurrentUser();
  const role = effectiveRole(user);
  const { mode, canSwitch, ready } = useAppMode();

  if (!ready) {
    return (
      <ScreenContainer edges={['top', 'bottom']}>
        <EmptyState title="Loading…" message="Preparing your workspace." icon="hourglass-outline" />
      </ScreenContainer>
    );
  }

  const shellKey = `mode-${mode}-role-${role ?? 'none'}-dual-${canSwitch ? 1 : 0}`;

  let body: React.ReactElement;

  if (canSwitch && mode === 'home') {
    body = <ParentTabNavigator />;
  } else if (
    (role === UserRole.DIRECTOR || isAdminAppRole(role)) &&
    (user?.parentId || user?.canHomeMode)
  ) {
    // Admin dual identity: Home = parent shell; Work = Admin app hand-off (not teacher UI).
    body = mode === 'work' && canSwitch ? <OpenAdminAppScreen /> : <ParentTabNavigator />;
  } else if (
    role === UserRole.TEACHER ||
    role === UserRole.SENIOR_TEACHER ||
    role === UserRole.SUPERVISOR
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
