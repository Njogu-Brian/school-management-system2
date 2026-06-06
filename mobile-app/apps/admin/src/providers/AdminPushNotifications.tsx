import { useAuth, usePushNotifications, UserRole } from '@erp/core';
import React from 'react';

/**
 * Registers push tokens for Super Admin users so critical system alerts can reach the device.
 */
export const AdminPushNotifications: React.FC = () => {
  const { user } = useAuth();
  const enabled = user?.role === UserRole.SUPER_ADMIN;
  usePushNotifications(enabled);
  return null;
};
