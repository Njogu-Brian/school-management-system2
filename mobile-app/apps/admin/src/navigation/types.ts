import type { NavigatorScreenParams } from '@react-navigation/native';

/**
 * Bottom-tab routes (the most-used admin areas — build plan §5.2 default preset).
 */
export type TabsParamList = {
  Dashboard: undefined;
  Students: undefined;
  Finance: undefined;
  People: undefined;
};

/**
 * Drawer routes (the full module list — IA §1). `Workspace` hosts the bottom tabs;
 * the remaining areas are drawer-only stacks in the shell.
 */
export type DrawerParamList = {
  Workspace: NavigatorScreenParams<TabsParamList> | undefined;
  Admissions: undefined;
  Academics: undefined;
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
