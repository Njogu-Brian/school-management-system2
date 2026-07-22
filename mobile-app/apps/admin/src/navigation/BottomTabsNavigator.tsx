import { Ionicons } from '@expo/vector-icons';
import type { AdminAreaKey } from '@erp/core';
import { useRbac } from '@erp/core';
import { PremiumTabBar, useTheme } from '@erp/ui';
import { AppHeaderChrome } from './AppHeaderChrome';
import { DrawerActions } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import type { BottomTabBarProps } from '@react-navigation/bottom-tabs';
import React, { useCallback } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { DashboardStackNavigator } from './DashboardStackNavigator';
import { FinanceStackNavigator } from './FinanceStackNavigator';
import { PeopleStackNavigator } from './PeopleStackNavigator';
import { StudentsStackNavigator } from './StudentsStackNavigator';
import { withAreaGuard } from './guards/ProtectedAreaScreen';
import { navigateTabHome } from './areaRoutes';
import type { TabsParamList } from './types';

const Tab = createBottomTabNavigator<TabsParamList>();

const TAB_ICON: Record<keyof TabsParamList, keyof typeof Ionicons.glyphMap> = {
  Dashboard: 'grid-outline',
  Students: 'people-outline',
  Finance: 'cash-outline',
  People: 'briefcase-outline',
};

const TAB_ICON_FOCUSED: Record<keyof TabsParamList, keyof typeof Ionicons.glyphMap> = {
  Dashboard: 'grid',
  Students: 'people',
  Finance: 'cash',
  People: 'briefcase',
};

const TAB_AREA_KEY: Record<keyof TabsParamList, AdminAreaKey> = {
  Dashboard: 'dashboard',
  Students: 'students',
  Finance: 'finance',
  People: 'people',
};

const TAB_SCREENS: Record<keyof TabsParamList, React.ComponentType> = {
  Dashboard: DashboardStackNavigator,
  Students: StudentsStackNavigator,
  Finance: FinanceStackNavigator,
  People: PeopleStackNavigator,
};

const TAB_HEADER_LABEL: Record<keyof TabsParamList, string> = {
  Dashboard: 'Dashboard',
  Students: 'Students',
  Finance: 'Finance',
  People: 'Human Resource',
};

const TAB_BAR_LABEL: Record<keyof TabsParamList, string> = {
  Dashboard: 'Home',
  Students: 'Students',
  Finance: 'Finance',
  People: 'HR',
};

const TAB_TONE: Record<keyof TabsParamList, 'blue' | 'indigo' | 'emerald' | 'cyan'> = {
  Dashboard: 'blue',
  Students: 'indigo',
  Finance: 'emerald',
  People: 'cyan',
};

const NoTabsFallback: React.FC = () => {
  const { palette, typography } = useTheme();
  return (
    <View style={styles.fallback}>
      <Text style={{ color: palette.textSub, fontSize: typography.body.fontSize, textAlign: 'center' }}>
        No modules are assigned to your account for quick access. Open the menu to reach your
        modules.
      </Text>
    </View>
  );
};

function AdminFloatingTabBar({ state, navigation }: BottomTabBarProps) {
  const items = state.routes.map((route) => {
    const name = route.name as keyof TabsParamList;
    return {
      key: route.name,
      label: TAB_BAR_LABEL[name],
      icon: TAB_ICON[name],
      iconFocused: TAB_ICON_FOCUSED[name],
      tone: TAB_TONE[name],
    };
  });
  const activeKey = state.routes[state.index]?.name ?? items[0]?.key;

  const onTabPress = useCallback(
    (key: string) => {
      const routeName = key as keyof TabsParamList;
      navigateTabHome(navigation as never, routeName);
    },
    [navigation],
  );

  return <PremiumTabBar items={items} activeKey={activeKey} onTabPress={onTabPress} />;
}

export const BottomTabsNavigator: React.FC = () => {
  const { tabAreas } = useRbac();

  const allowedRoutes = tabAreas
    .map((a) => {
      const entry = Object.entries(TAB_AREA_KEY).find(([, key]) => key === a.key);
      return entry ? (entry[0] as keyof TabsParamList) : null;
    })
    .filter((r): r is keyof TabsParamList => r != null);

  if (allowedRoutes.length === 0) {
    return <NoTabsFallback />;
  }

  const initialRoute = allowedRoutes[0];

  return (
    <Tab.Navigator
      initialRouteName={initialRoute}
      tabBar={(props) => <AdminFloatingTabBar {...props} />}
      screenOptions={({ navigation, route }) => ({
        headerShown: true,
        header: () => (
          <AppHeaderChrome
            title={TAB_HEADER_LABEL[route.name]}
            onMenuPress={() => navigation.dispatch(DrawerActions.openDrawer())}
            showGlobalSearch={route.name === 'Dashboard'}
          />
        ),
      })}
    >
      {allowedRoutes.map((routeName) => (
        <Tab.Screen
          key={routeName}
          name={routeName}
          component={withAreaGuard(TAB_AREA_KEY[routeName], TAB_SCREENS[routeName])}
          options={{ tabBarLabel: TAB_BAR_LABEL[routeName] }}
        />
      ))}
    </Tab.Navigator>
  );
};

const styles = StyleSheet.create({
  fallback: {
    flex: 1,
    justifyContent: 'center',
    paddingHorizontal: 32,
  },
});
