import type { AdminAreaKey } from '@erp/core';
import type { BottomTabNavigationProp } from '@react-navigation/bottom-tabs';
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

/** Pop a tab stack back to its home screen (menu re-tap or drawer navigation). */
export function navigateTabHome(
  navigation: BottomTabNavigationProp<TabsParamList>,
  tab: keyof TabsParamList,
): void {
  switch (tab) {
    case 'Dashboard':
      navigation.navigate('Dashboard', { screen: 'DashboardHome' });
      break;
    case 'Students':
      navigation.navigate('Students', { screen: 'StudentRegistry' });
      break;
    case 'Finance':
      navigation.navigate('Finance', { screen: 'FinanceDashboard' });
      break;
    case 'People':
      navigation.navigate('People', { screen: 'StaffRegistry' });
      break;
    default:
      break;
  }
}
