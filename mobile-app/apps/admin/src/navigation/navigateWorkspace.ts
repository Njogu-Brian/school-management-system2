import { CommonActions } from '@react-navigation/native';
import type { DrawerParamList, TabsParamList } from './types';

/** Minimal navigation surface for cross-workspace jumps (avoids strict ParamList coupling). */
export type WorkspaceNavigation = {
  getParent: () => WorkspaceNavigation | undefined;
  dispatch: (action: ReturnType<typeof CommonActions.navigate>) => void;
};

function getDrawerNavigation(navigation: WorkspaceNavigation): WorkspaceNavigation {
  let current: WorkspaceNavigation = navigation;
  while (current.getParent()) {
    current = current.getParent() as WorkspaceNavigation;
  }
  return current;
}

/** Switch bottom tab (and optional nested stack screen). */
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
        params: screen ? { screen, params } : undefined,
      },
    }),
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
      params: screen ? { screen, params } : undefined,
    }),
  );
}
