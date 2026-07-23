import { CommonActions } from '@react-navigation/native';
import type { DrawerParamList, TabsParamList } from './types';

import type { DashboardStackParamList } from './dashboardStackTypes';

/** Minimal navigation surface for cross-workspace jumps (avoids strict ParamList coupling). */
export type WorkspaceNavigation = {
  getParent: () => WorkspaceNavigation | undefined;
  dispatch: (action: ReturnType<typeof CommonActions.navigate>) => void;
  navigate?: (name: string, params?: object) => void;
};

function getDrawerNavigation(navigation: WorkspaceNavigation): WorkspaceNavigation {
  let current: WorkspaceNavigation = navigation;
  while (current.getParent()) {
    current = current.getParent() as WorkspaceNavigation;
  }
  return current;
}

/**
 * Switch bottom tab (and optional nested stack screen).
 * Nested `screen` must be passed without `initial: false` quirks that drop the target
 * and leave the tab on its home route (e.g. Notifications → DashboardHome).
 */
export function navigateToTab(
  navigation: WorkspaceNavigation,
  tab: keyof TabsParamList,
  screen?: string,
  params?: object,
): void {
  const root = getDrawerNavigation(navigation);
  root.dispatch(
    CommonActions.navigate({
      name: 'Workspace',
      params: {
        screen: tab,
        params: screen
          ? {
              screen,
              params: params ?? {},
            }
          : undefined,
      },
      merge: true,
    } as Parameters<typeof CommonActions.navigate>[0]),
  );
}

/** Open a drawer-only workspace (and optional nested stack screen). */
export function navigateToDrawer(
  navigation: WorkspaceNavigation,
  drawer: Exclude<keyof DrawerParamList, 'Workspace'>,
  screen?: string,
  params?: object,
): void {
  const root = getDrawerNavigation(navigation);
  root.dispatch(
    CommonActions.navigate({
      name: drawer,
      params: screen
        ? {
            screen,
            params,
          }
        : undefined,
      merge: true,
    } as Parameters<typeof CommonActions.navigate>[0]),
  );
}

/** Back from dashboard stack screens opened via tab jump (no history). */
export function navigateDashboardBack(navigation: {
  canGoBack: () => boolean;
  goBack: () => void;
  navigate: (screen: keyof DashboardStackParamList) => void;
}): void {
  if (navigation.canGoBack()) {
    navigation.goBack();
  } else {
    navigation.navigate('DashboardHome');
  }
}
