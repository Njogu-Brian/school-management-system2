import type { AdminAreaKey } from '@erp/core';
import { CommonActions } from '@react-navigation/native';
import type { BottomTabNavigationProp } from '@react-navigation/bottom-tabs';
import type { DrawerNavigationProp } from '@react-navigation/drawer';
import type { DrawerParamList, TabsParamList } from './types';

/** Maps IA area keys to bottom-tab route names. */
export const AREA_TO_TAB_ROUTE: Partial<Record<AdminAreaKey, keyof TabsParamList>> = {
  dashboard: 'Dashboard',
  students: 'Students',
  finance: 'Finance',
  people: 'People',
};

/** Maps IA area keys to drawer-only route names. */
export const AREA_TO_DRAWER_ROUTE: Partial<
  Record<AdminAreaKey, Exclude<keyof DrawerParamList, 'Workspace'>>
> = {
  approvals: 'Approvals',
  admissions: 'Admissions',
  academics: 'Academics',
  operations: 'Operations',
  communication: 'Communication',
  reports: 'Reports',
  settings: 'Settings',
};

/** Default (home) screen for each drawer stack (omit single-screen modules). */
export const DRAWER_HOME_SCREEN: Partial<
  Record<Exclude<keyof DrawerParamList, 'Workspace'>, string>
> = {
  Approvals: 'ApprovalsHome',
  Admissions: 'AdmissionsWorkspace',
  Academics: 'AcademicsDashboard',
  Operations: 'OperationsDashboard',
  Communication: 'CommunicationDashboard',
  Reports: 'ReportsHub',
};

export const TAB_HOME_SCREEN: Record<keyof TabsParamList, string> = {
  Dashboard: 'DashboardHome',
  Students: 'StudentRegistry',
  Finance: 'FinanceDashboard',
  People: 'StaffRegistry',
};

/** Pop a tab stack back to its home screen (menu re-tap or drawer navigation). */
export function navigateTabHome(
  navigation: BottomTabNavigationProp<TabsParamList>,
  tab: keyof TabsParamList,
): void {
  const homeScreen = TAB_HOME_SCREEN[tab];
  switch (tab) {
    case 'Dashboard':
      navigation.navigate('Dashboard', { screen: homeScreen as 'DashboardHome' });
      break;
    case 'Students':
      navigation.navigate('Students', { screen: homeScreen as 'StudentRegistry' });
      break;
    case 'Finance':
      navigation.navigate('Finance', { screen: homeScreen as 'FinanceDashboard' });
      break;
    case 'People':
      navigation.navigate('People', { screen: homeScreen as 'StaffRegistry' });
      break;
    default:
      break;
  }
}

type DrawerAreaNavigation = {
  dispatch: DrawerNavigationProp<DrawerParamList>['dispatch'];
  navigate: DrawerNavigationProp<DrawerParamList>['navigate'];
};

/** Drawer / sidebar: always land on the module home (even when re-selecting the active area). */
export function navigateDrawerAreaHome(
  navigation: DrawerAreaNavigation,
  areaKey: AdminAreaKey,
): void {
  const tabRoute = AREA_TO_TAB_ROUTE[areaKey];
  if (tabRoute) {
    navigation.dispatch(
      CommonActions.navigate({
        name: 'Workspace',
        params: {
          screen: tabRoute,
          params: { screen: TAB_HOME_SCREEN[tabRoute] },
        },
        merge: false,
      }),
    );
    return;
  }

  const drawerRoute = AREA_TO_DRAWER_ROUTE[areaKey];
  if (!drawerRoute) {
    return;
  }

  const homeScreen = DRAWER_HOME_SCREEN[drawerRoute];
  if (homeScreen) {
    navigation.dispatch(
      CommonActions.navigate({
        name: drawerRoute,
        params: { screen: homeScreen },
        merge: false,
      }),
    );
    return;
  }

  if (drawerRoute === 'Settings') {
    navigation.dispatch(
      CommonActions.navigate({
        name: 'Settings',
        params: { resetAt: Date.now() },
        merge: false,
      }),
    );
    return;
  }

  navigation.navigate(drawerRoute);
}
