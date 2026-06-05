import { usePendingApprovals, useUnreadNotificationCount } from '@erp/core';
import { GlobalAppHeader } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useCallback } from 'react';
import { navigateToDrawer, navigateToTab } from './navigateWorkspace';

export interface AppHeaderChromeProps {
  title: string;
  onMenuPress?: () => void;
}

export const AppHeaderChrome: React.FC<AppHeaderChromeProps> = ({ title, onMenuPress }) => {
  const navigation = useNavigation();
  const unreadQuery = useUnreadNotificationCount();
  const approvalsQuery = usePendingApprovals();

  const onSearch = useCallback(
    () => navigateToTab(navigation, 'Dashboard', 'GlobalSearch'),
    [navigation],
  );
  const onNotifications = useCallback(
    () => navigateToTab(navigation, 'Dashboard', 'NotificationsList'),
    [navigation],
  );
  const onApprovals = useCallback(
    () => navigateToDrawer(navigation, 'Approvals', 'ApprovalsHome'),
    [navigation],
  );
  const onProfile = useCallback(
    () => navigateToDrawer(navigation, 'Settings'),
    [navigation],
  );

  const approvalCount = approvalsQuery.data?.total ?? 0;

  return (
    <GlobalAppHeader
      title={title}
      onMenuPress={onMenuPress}
      onSearchPress={onSearch}
      onNotificationsPress={onNotifications}
      onApprovalsPress={onApprovals}
      onProfilePress={onProfile}
      showNotificationsBadge={(unreadQuery.data ?? 0) > 0}
      showApprovalsBadge={approvalCount > 0}
    />
  );
};
