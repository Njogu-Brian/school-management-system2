import { useAuth, usePushNotifications, UserRole } from '@erp/core';
import React from 'react';

/**
 * Registers push tokens for Super Admin and Secretary users so actionable alerts reach the device.
 */
export const AdminPushNotifications: React.FC = () => {
  const { user } = useAuth();
  const enabled =
    user?.role === UserRole.SUPER_ADMIN || user?.role === UserRole.SECRETARY;
  usePushNotifications(enabled);
  return null;
};
