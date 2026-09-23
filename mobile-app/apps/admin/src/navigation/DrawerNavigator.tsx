import { AppHeaderChrome } from './AppHeaderChrome';
import { createStackNavigator, StackNavigationOptions } from '@react-navigation/stack';
import React from 'react';
import { AcademicsStackNavigator } from './AcademicsStackNavigator';
import { AdmissionsStackNavigator } from './AdmissionsStackNavigator';
import { ApprovalsStackNavigator } from './ApprovalsStackNavigator';
import { CommunicationStackNavigator } from './CommunicationStackNavigator';
import { OperationsStackNavigator } from './OperationsStackNavigator';
import { ReportsStackNavigator } from './ReportsStackNavigator';
import { SettingsStackNavigator } from './SettingsStackNavigator';
import { AREA_TO_DRAWER_ROUTE } from './areaRoutes';
import { BottomTabsNavigator } from './BottomTabsNavigator';
import { withWorkspaceTabBar } from './PersistentWorkspaceTabBar';
import { withAreaGuard } from './guards/ProtectedAreaScreen';
import type { DrawerParamList } from './types';
import { useRbac } from '@erp/core';

/**
 * Root workspace shell — formerly a drawer. Side nav was removed; modules open
 * from the Home screen. Kept as a stack so secondary areas stay navigable.
 */
const Stack = createStackNavigator<DrawerParamList>();

function headerOptions(title: string): StackNavigationOptions {
  return {
    headerShown: true,
    header: () => <AppHeaderChrome title={title} />,
  };
}

const MODULE_SCREENS: Array<{
  areaKey: keyof typeof AREA_TO_DRAWER_ROUTE;
  component: React.ComponentType;
  title: string;
}> = [
  { areaKey: 'approvals', component: ApprovalsStackNavigator, title: 'Approvals' },
  { areaKey: 'admissions', component: AdmissionsStackNavigator, title: 'Admissions' },
  { areaKey: 'academics', component: AcademicsStackNavigator, title: 'Academics' },
  { areaKey: 'operations', component: OperationsStackNavigator, title: 'Operations' },
  { areaKey: 'communication', component: CommunicationStackNavigator, title: 'Communication' },
  { areaKey: 'reports', component: ReportsStackNavigator, title: 'Reports' },
  { areaKey: 'settings', component: SettingsStackNavigator, title: 'Settings' },
];

export const DrawerNavigator: React.FC = () => {
  const { drawerAreas, tabAreas } = useRbac();

  const allowedDrawerKeys = new Set(drawerAreas.map((a) => a.key));

  const visibleModuleScreens = MODULE_SCREENS.filter(({ areaKey }) => {
    const routeName = AREA_TO_DRAWER_ROUTE[areaKey];
    return Boolean(routeName) && allowedDrawerKeys.has(areaKey);
  });
  const firstModule = visibleModuleScreens[0];
  const initialRoute =
    tabAreas.length > 0
      ? 'Workspace'
      : firstModule
        ? AREA_TO_DRAWER_ROUTE[firstModule.areaKey]
        : 'Workspace';

  return (
    <Stack.Navigator
      initialRouteName={initialRoute}
      screenOptions={{
        headerShown: false,
        animationEnabled: true,
      }}
    >
      <Stack.Screen name="Workspace" component={BottomTabsNavigator} options={{ headerShown: false }} />

      {visibleModuleScreens.map(({ areaKey, component, title }) => {
        const routeName = AREA_TO_DRAWER_ROUTE[areaKey];
        if (!routeName) return null;
        return (
          <Stack.Screen
            key={routeName}
            name={routeName}
            component={withAreaGuard(areaKey, withWorkspaceTabBar(component))}
            options={headerOptions(title)}
          />
        );
      })}
    </Stack.Navigator>
  );
};
