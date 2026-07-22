import { useAuth, usePushNotifications, UserRole } from '@erp/core';
import React from 'react';

/** Registers push for Users App roles (teachers, parents, students, drivers). */
export const UsersPushNotifications: React.FC = () => {
  const { user } = useAuth();
  const enabled =
    user?.role === UserRole.TEACHER ||
    user?.role === UserRole.SENIOR_TEACHER ||
    user?.role === UserRole.SUPERVISOR ||
    user?.role === UserRole.PARENT ||
    user?.role === UserRole.GUARDIAN ||
    user?.role === UserRole.STUDENT ||
    user?.role === UserRole.DRIVER ||
    user?.role === UserRole.TRANSPORT;
  usePushNotifications(enabled);
  return null;
};
