import type { NavigatorScreenParams } from '@react-navigation/native';
import type { AcademicsStackParamList } from './academicsStackTypes';
import type { AdmissionsStackParamList } from './admissionsStackTypes';
import type { ApprovalsStackParamList } from './approvalsStackTypes';
import type { DashboardStackParamList } from './dashboardStackTypes';
import type { FinanceStackParamList } from './financeStackTypes';
import type { PeopleStackParamList } from './peopleStackTypes';
import type { StudentsStackParamList } from './studentsStackTypes';

/**
 * Bottom-tab routes (the most-used admin areas — build plan §5.2 default preset).
 */
export type TabsParamList = {
  Dashboard: NavigatorScreenParams<DashboardStackParamList> | undefined;
  Students: NavigatorScreenParams<StudentsStackParamList> | undefined;
  Finance: NavigatorScreenParams<FinanceStackParamList> | undefined;
  People: NavigatorScreenParams<PeopleStackParamList> | undefined;
};

/**
 * Drawer routes (the full module list — IA §1). `Workspace` hosts the bottom tabs;
 * the remaining areas are drawer-only stacks in the shell.
 */
export type DrawerParamList = {
  Workspace: NavigatorScreenParams<TabsParamList> | undefined;
  Approvals: NavigatorScreenParams<ApprovalsStackParamList> | undefined;
  Admissions: NavigatorScreenParams<AdmissionsStackParamList> | undefined;
  Academics: NavigatorScreenParams<AcademicsStackParamList> | undefined;
  Operations: undefined;
  Communication: undefined;
  Reports: undefined;
  Settings: undefined;
};

declare global {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace ReactNavigation {
    interface RootParamList extends DrawerParamList {}
  }
}
