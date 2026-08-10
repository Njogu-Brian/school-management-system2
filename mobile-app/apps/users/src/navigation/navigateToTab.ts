type CrossTabNav = {
  navigate: (name: string, params?: object) => void;
  getParent: () => CrossTabNav | undefined;
  getState?: () => { index?: number; key?: string; routes?: Array<{ name: string }> };
  canGoBack?: () => boolean;
  goBack?: () => void;
};

/**
 * Jump to a sibling bottom tab (and optional nested stack screen).
 * When `tabHomeScreen` is set and differs from `screen`, the tab stack is opened as
 * [tab home → screen] so Back returns to the tab home instead of leaving the tab
 * (e.g. Home → More/Transport → Back → More, not Home).
 */
export function navigateToTab(
  navigation: CrossTabNav,
  tab: string,
  screen?: string,
  params?: object,
  tabHomeScreen?: string,
): void {
  const parent = navigation.getParent?.() ?? navigation;
  if (!screen) {
    parent.navigate(tab);
    return;
  }
  if (tabHomeScreen && tabHomeScreen !== screen) {
    parent.navigate(tab, {
      state: {
        routes: [{ name: tabHomeScreen }, { name: screen, params }],
        index: 1,
      },
    });
    return;
  }
  parent.navigate(tab, {
    screen,
    params,
  });
}

/**
 * Back within the current tab stack only.
 * Plain `goBack()` can leave the tab when the detail was opened as the tab's only route
 * (history bubbles to the previous tab). In that case, land on `homeScreen`.
 */
export function goBackInStack(navigation: CrossTabNav, ...homeScreens: string[]): void {
  const index = navigation.getState?.()?.index ?? 0;
  if (index > 0 && navigation.goBack) {
    navigation.goBack();
    return;
  }
  const home = homeScreens[0];
  if (home) navigation.navigate(home);
}
