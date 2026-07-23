import type { Ionicons } from '@expo/vector-icons';
import { PremiumTabBar, type PremiumTabItem } from '@erp/ui';
import type { BottomTabBarProps } from '@react-navigation/bottom-tabs';
import React from 'react';

export type UsersTabConfig = Record<
  string,
  {
    label: string;
    icon: keyof typeof Ionicons.glyphMap;
    iconFocused?: keyof typeof Ionicons.glyphMap;
    tone?: PremiumTabItem['tone'];
  }
>;

/**
 * Builds a floating `PremiumTabBar` (Admin's chrome) for a given role's bottom tab
 * route names. Usage: `tabBar={createUsersTabBar({ Home: {...}, Classes: {...} })}`.
 */
export function createUsersTabBar(config: UsersTabConfig): (props: BottomTabBarProps) => React.ReactElement {
  const UsersTabBar = ({ state, navigation }: BottomTabBarProps): React.ReactElement => {
    const items: PremiumTabItem[] = state.routes
      .filter((route) => config[route.name])
      .map((route) => ({ key: route.name, ...config[route.name] }));
    const activeKey = state.routes[state.index]?.name ?? items[0]?.key;

    const onTabPress = (key: string) => {
      const route = state.routes.find((r) => r.name === key);
      if (!route) return;
      const event = navigation.emit({
        type: 'tabPress',
        target: route.key,
        canPreventDefault: true,
      });
      if (!event.defaultPrevented) {
        navigation.navigate(route.name);
      }
    };

    return <PremiumTabBar items={items} activeKey={activeKey} onTabPress={onTabPress} />;
  };
  return UsersTabBar;
}
