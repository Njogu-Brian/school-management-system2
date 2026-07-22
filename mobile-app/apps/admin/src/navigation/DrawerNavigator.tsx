import { AppHeaderChrome } from './AppHeaderChrome';
import {
  createDrawerNavigator,
  DrawerNavigationOptions,
  DrawerNavigationProp,
} from '@react-navigation/drawer';
import { DrawerActions } from '@react-navigation/native';
import React from 'react';
import { useWindowDimensions } from 'react-native';
import { AcademicsStackNavigator } from './AcademicsStackNavigator';
import { AdmissionsStackNavigator } from './AdmissionsStackNavigator';
import { ApprovalsStackNavigator } from './ApprovalsStackNavigator';
import { CommunicationStackNavigator } from './CommunicationStackNavigator';
import { OperationsStackNavigator } from './OperationsStackNavigator';
import { ReportsStackNavigator } from './ReportsStackNavigator';
import { SettingsScreen } from '../features/settings';
import { AREA_TO_DRAWER_ROUTE } from './areaRoutes';
import { BottomTabsNavigator } from './BottomTabsNavigator';
import { DrawerContent } from './DrawerContent';
import { withWorkspaceTabBar } from './PersistentWorkspaceTabBar';
import { withAreaGuard } from './guards/ProtectedAreaScreen';
import type { DrawerParamList } from './types';
import { useRbac } from '@erp/core';

const Drawer = createDrawerNavigator<DrawerParamList>();

function headerOptions(title: string) {
  return ({
    navigation,
  }: {
    navigation: DrawerNavigationProp<DrawerParamList>;
  }): DrawerNavigationOptions => ({
    headerShown: true,
    header: () => (
      <AppHeaderChrome
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
  { areaKey: 'approvals', component: ApprovalsStackNavigator, title: 'Approvals' },
  { areaKey: 'admissions', component: AdmissionsStackNavigator, title: 'Admissions' },
  { areaKey: 'academics', component: AcademicsStackNavigator, title: 'Academics' },
  { areaKey: 'operations', component: OperationsStackNavigator, title: 'Operations' },
  { areaKey: 'communication', component: CommunicationStackNavigator, title: 'Communication' },
  { areaKey: 'reports', component: ReportsStackNavigator, title: 'Reports' },
  { areaKey: 'settings', component: SettingsScreen, title: 'Settings' },
];

export const DrawerNavigator: React.FC = () => {
  const { drawerAreas, tabAreas } = useRbac();
  const { width: windowWidth } = useWindowDimensions();

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

  const drawerWidth = Math.min(280, Math.round(windowWidth * 0.72));

  return (
    <Drawer.Navigator
      initialRouteName={initialRoute}
      drawerContent={(props) => <DrawerContent {...props} />}
      screenOptions={{
        headerShown: false,
        drawerType: 'front',
        overlayColor: 'rgba(0,0,0,0.45)',
        drawerStyle: {
          width: drawerWidth,
          backgroundColor: 'transparent',
          borderTopRightRadius: 24,
          borderBottomRightRadius: 24,
          overflow: 'hidden',
        },
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
            component={withAreaGuard(areaKey, withWorkspaceTabBar(component))}
            options={headerOptions(title)}
          />
        );
      })}
    </Drawer.Navigator>
  );
};
