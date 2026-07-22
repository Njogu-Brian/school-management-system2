import { useCurrentUser, UserRole } from '@erp/core';
import { EmptyState, ScreenContainer } from '@erp/ui';
import React from 'react';
import { DriverTabNavigator } from './driver/DriverTabNavigator';
import { ParentTabNavigator } from './parent/ParentTabNavigator';
import { StudentTabNavigator } from './student/StudentTabNavigator';
import { TeacherNavigator } from './teacher/TeacherNavigator';

/**
 * Role-adaptive shell — one binary, tabs chosen by role at runtime.
 */
export const RoleBasedNavigator: React.FC = () => {
  const user = useCurrentUser();
  const role = user?.role;

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
        message="Your account role is recognized for this app but has no dedicated shell yet. Contact your school administrator."
        icon="help-circle-outline"
      />
    </ScreenContainer>
  );
};
