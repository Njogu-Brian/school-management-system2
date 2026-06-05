import { useRbac } from '@erp/core';
import { GlobalAppHeader, useTheme } from '@erp/ui';
import {
  createDrawerNavigator,
  DrawerNavigationOptions,
  DrawerNavigationProp,
} from '@react-navigation/drawer';
import { DrawerActions } from '@react-navigation/native';
import React from 'react';
import { AcademicsStackNavigator } from './AcademicsStackNavigator';
import { AdmissionsStackNavigator } from './AdmissionsStackNavigator';
import { CommunicationScreen } from '../features/communication';
import { OperationsScreen } from '../features/operations';
import { ReportsScreen } from '../features/reports';
import { SettingsScreen } from '../features/settings';
import { AREA_TO_DRAWER_ROUTE } from './areaRoutes';
import { BottomTabsNavigator } from './BottomTabsNavigator';
import { DrawerContent } from './DrawerContent';
import { withAreaGuard } from './guards/ProtectedAreaScreen';
import type { DrawerParamList } from './types';

const Drawer = createDrawerNavigator<DrawerParamList>();

function headerOptions(title: string) {
  return ({
    navigation,
  }: {
    navigation: DrawerNavigationProp<DrawerParamList>;
  }): DrawerNavigationOptions => ({
    headerShown: true,
    header: () => (
      <GlobalAppHeader
        title={title}
        onMenuPress={() => navigation.dispatch(DrawerActions.openDrawer())}
      />
    ),
  });
}

const DRAWER_SCREENS: Array<{
  areaKey: keyof typeof AREA_TO_DRAWER_ROUTE;
  component: React.ComponentType;
  title: string;
}> = [
  { areaKey: 'admissions', component: AdmissionsStackNavigator, title: 'Admissions' },
  { areaKey: 'academics', component: AcademicsStackNavigator, title: 'Academics' },
  { areaKey: 'operations', component: OperationsScreen, title: 'Operations' },
  { areaKey: 'communication', component: CommunicationScreen, title: 'Communication' },
  { areaKey: 'reports', component: ReportsScreen, title: 'Reports' },
  { areaKey: 'settings', component: SettingsScreen, title: 'Settings' },
];

export const DrawerNavigator: React.FC = () => {
  const { palette } = useTheme();
  const { drawerAreas, tabAreas } = useRbac();

  const allowedDrawerKeys = new Set(drawerAreas.map((a) => a.key));

  const firstDrawerScreen = DRAWER_SCREENS.find(({ areaKey }) =>
    allowedDrawerKeys.has(areaKey),
  );
  const initialRoute =
    tabAreas.length > 0
      ? 'Workspace'
      : firstDrawerScreen
        ? AREA_TO_DRAWER_ROUTE[firstDrawerScreen.areaKey]
        : 'Workspace';

  return (
    <Drawer.Navigator
      initialRouteName={initialRoute}
      drawerContent={(props) => <DrawerContent {...props} />}
      screenOptions={{
        headerShown: false,
        drawerStyle: { backgroundColor: palette.surface },
      }}
    >
      {tabAreas.length > 0 ? (
        <Drawer.Screen
          name="Workspace"
          component={BottomTabsNavigator}
          options={{ headerShown: false }}
        />
      ) : null}

      {DRAWER_SCREENS.map(({ areaKey, component, title }) => {
        const routeName = AREA_TO_DRAWER_ROUTE[areaKey];
        if (!routeName || !allowedDrawerKeys.has(areaKey)) {
          return null;
        }
        return (
          <Drawer.Screen
            key={routeName}
            name={routeName}
            component={withAreaGuard(areaKey, component)}
            options={headerOptions(title)}
          />
        );
      })}
    </Drawer.Navigator>
  );
};
