import type { AdminAreaKey } from '@erp/core';
import type { TabsParamList } from './types';

/** Maps IA area keys to bottom-tab route names. */
export const AREA_TO_TAB_ROUTE: Partial<Record<AdminAreaKey, keyof TabsParamList>> = {
  dashboard: 'Dashboard',
  students: 'Students',
  finance: 'Finance',
  people: 'People',
};

/** Maps IA area keys to drawer-only route names. */
export const AREA_TO_DRAWER_ROUTE: Partial<
  Record<AdminAreaKey, Exclude<keyof import('./types').DrawerParamList, 'Workspace'>>
> = {
  approvals: 'Approvals',
  admissions: 'Admissions',
  academics: 'Academics',
  operations: 'Operations',
  communication: 'Communication',
  reports: 'Reports',
  settings: 'Settings',
};
