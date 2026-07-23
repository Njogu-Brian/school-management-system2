import { useUnreadNotificationCount } from '@erp/core';
import { GlobalAppHeader, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useCallback } from 'react';

export interface UsersAppHeaderChromeProps {
  title: string;
  /** Screen to open for the profile icon — defaults to `MyProfile`. */
  profileRoute?: string;
  /** Screen to open for the notifications icon — defaults to `Notifications`. */
  notificationsRoute?: string;
  /**
   * Optional menu affordance. Admin's chrome opens a drawer; the Users app has no
   * drawer, so pass a callback that jumps to the tab's "More" hub instead (or omit
   * to hide the menu button entirely).
   */
  onMenuPress?: () => void;
}

/**
 * Users-app equivalent of Admin's `AppHeaderChrome` — same `GlobalAppHeader` chrome
 * (brand strip, theme toggle, notifications, profile) without the drawer menu.
 * Rendered on tab-root screens only (see each role navigator).
 */
export const UsersAppHeaderChrome: React.FC<UsersAppHeaderChromeProps> = ({
  title,
  profileRoute = 'MyProfile',
  notificationsRoute = 'Notifications',
  onMenuPress,
}) => {
  const navigation = useNavigation();
  const { toggleTheme } = useTheme();
  const unreadQuery = useUnreadNotificationCount();

  const onNotifications = useCallback(
    () => navigation.navigate(notificationsRoute as never),
    [navigation, notificationsRoute],
  );
  const onProfile = useCallback(
    () => navigation.navigate(profileRoute as never),
    [navigation, profileRoute],
  );

  return (
    <GlobalAppHeader
      title={title}
      onMenuPress={onMenuPress}
      onNotificationsPress={onNotifications}
      onThemeTogglePress={toggleTheme}
      onProfilePress={onProfile}
      showNotificationsBadge={(unreadQuery.data ?? 0) > 0}
    />
  );
};
