import { useAuth, usePushNotifications, UserRole } from '@erp/core';
import React from 'react';

/**
 * Registers push tokens for Super Admin so system errors reach that device only.
 */
export const AdminPushNotifications: React.FC = () => {
  const { user } = useAuth();
  const enabled = user?.role === UserRole.SUPER_ADMIN;
  usePushNotifications(enabled);
  return null;
};
