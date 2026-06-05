import { Ionicons } from '@expo/vector-icons';
import type { AdminAreaKey } from '@erp/core';
import { useRbac } from '@erp/core';
import { useTheme } from '@erp/ui';
import { AppHeaderChrome } from './AppHeaderChrome';
import { DrawerActions } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { DashboardStackNavigator } from './DashboardStackNavigator';
import { FinanceStackNavigator } from './FinanceStackNavigator';
import { PeopleStackNavigator } from './PeopleStackNavigator';
import { StudentsStackNavigator } from './StudentsStackNavigator';
import { withAreaGuard } from './guards/ProtectedAreaScreen';
import type { TabsParamList } from './types';

const Tab = createBottomTabNavigator<TabsParamList>();

const TAB_ICON: Record<keyof TabsParamList, keyof typeof Ionicons.glyphMap> = {
  Dashboard: 'grid-outline',
  Students: 'people-outline',
  Finance: 'cash-outline',
  People: 'briefcase-outline',
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

const TAB_LABEL: Record<keyof TabsParamList, string> = {
  Dashboard: 'Dashboard',
  Students: 'Students',
  Finance: 'Finance',
  People: 'People',
};

const NoTabsFallback: React.FC = () => {
  const { palette, fontSizes } = useTheme();
  return (
    <View style={styles.fallback}>
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.md, textAlign: 'center' }}>
        No modules are assigned to your account for quick access. Open the menu to reach your
        modules.
      </Text>
    </View>
  );
};

export const BottomTabsNavigator: React.FC = () => {
  const { palette, colors } = useTheme();
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
      screenOptions={({ navigation, route }) => ({
        headerShown: true,
        header: () => (
          <AppHeaderChrome
            title={TAB_LABEL[route.name]}
            onMenuPress={() => navigation.dispatch(DrawerActions.openDrawer())}
          />
        ),
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: palette.textSecondary,
        tabBarStyle: {
          backgroundColor: palette.surface,
          borderTopColor: palette.border,
        },
        tabBarIcon: ({ color, size }) => (
          <Ionicons name={TAB_ICON[route.name]} size={size} color={color} />
        ),
      })}
    >
      {allowedRoutes.map((routeName) => (
        <Tab.Screen
          key={routeName}
          name={routeName}
          component={withAreaGuard(TAB_AREA_KEY[routeName], TAB_SCREENS[routeName])}
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
