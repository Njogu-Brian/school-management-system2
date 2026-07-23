import { useUnreadNotificationCount } from '@erp/core';
import { GlobalAppHeader, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useCallback } from 'react';
import { navigateToTab } from './navigateWorkspace';

export interface AppHeaderChromeProps {
  title: string;
  onMenuPress?: () => void;
  /** Global “Search anything” prompt — dashboard only to avoid duplicate search bars. */
  showGlobalSearch?: boolean;
}

type NavLike = {
  navigate: (name: string, params?: object) => void;
  getState?: () => { routeNames?: string[] } | undefined;
  getParent?: () => NavLike | undefined;
};

function navigateDashboardNested(navigation: NavLike, screen: string): void {
  let current: NavLike | undefined = navigation;
  while (current) {
    const names = current.getState?.()?.routeNames ?? [];
    if (names.includes('Dashboard')) {
      current.navigate('Dashboard', { screen });
      return;
    }
    if (names.includes('Workspace')) {
      current.navigate('Workspace', {
        screen: 'Dashboard',
        params: { screen },
      });
      return;
    }
    current = current.getParent?.();
  }
  navigateToTab(navigation as never, 'Dashboard', screen);
}

export const AppHeaderChrome: React.FC<AppHeaderChromeProps> = ({
  title,
  onMenuPress,
  showGlobalSearch = false,
}) => {
  const navigation = useNavigation();
  const { toggleTheme } = useTheme();
  const unreadQuery = useUnreadNotificationCount();

  const onSearch = useCallback(
    () => navigateDashboardNested(navigation as unknown as NavLike, 'GlobalSearch'),
    [navigation],
  );
  const onNotifications = useCallback(
    () => navigateDashboardNested(navigation as unknown as NavLike, 'NotificationsList'),
    [navigation],
  );
  const onProfile = useCallback(
    () => navigateDashboardNested(navigation as unknown as NavLike, 'UserProfile'),
    [navigation],
  );

  return (
    <GlobalAppHeader
      title={title}
      onMenuPress={onMenuPress}
      onSearchPress={showGlobalSearch ? onSearch : undefined}
      onNotificationsPress={onNotifications}
      onThemeTogglePress={toggleTheme}
      onProfilePress={onProfile}
      showNotificationsBadge={(unreadQuery.data ?? 0) > 0}
    />
  );
};
