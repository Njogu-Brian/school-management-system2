import { useWindowDimensions } from 'react-native';

/** Android sw600dp / 7" tablets and up (shortest side). */
export const TABLET_SHORTEST_SIDE = 600;
/** Centered login / school-code card on tablet. */
export const LOGIN_FORM_MAX_WIDTH = 480;
/** Floating tab capsule should not stretch across a 10–12" tablet. */
export const TABLET_TAB_BAR_MAX_WIDTH = 560;
/** Comfortable reading column for forms and settings. */
export const TABLET_CONTENT_MAX_WIDTH = 840;
/** Permanent drawer / 4-column dashboards. */
export const LARGE_TABLET_MIN_WIDTH = 900;

export interface AdaptiveLayout {
  width: number;
  height: number;
  isLandscape: boolean;
  /** Shortest side ≥ 600dp — phone vs tablet, including rotation. */
  isTablet: boolean;
  /** Width ≥ 900dp — two-pane chrome. */
  isLargeTablet: boolean;
  formMaxWidth: number;
  tabBarMaxWidth: number;
  contentMaxWidth: number;
  gridColumns: 2 | 3 | 4;
  listColumns: 1 | 2;
}

/**
 * Size-class helper for a single phone+tablet Play Store APK.
 * Uses the shortest side so a phone in landscape does not pick up tablet chrome.
 */
export function useAdaptiveLayout(): AdaptiveLayout {
  const { width, height } = useWindowDimensions();
  const shortest = Math.min(width, height);
  const isTablet = shortest >= TABLET_SHORTEST_SIDE;
  const isLargeTablet = width >= LARGE_TABLET_MIN_WIDTH;

  return {
    width,
    height,
    isLandscape: width > height,
    isTablet,
    isLargeTablet,
    formMaxWidth: LOGIN_FORM_MAX_WIDTH,
    tabBarMaxWidth: TABLET_TAB_BAR_MAX_WIDTH,
    contentMaxWidth: TABLET_CONTENT_MAX_WIDTH,
    gridColumns: isLargeTablet ? 4 : isTablet ? 3 : 2,
    listColumns: isTablet && width >= 720 ? 2 : 1,
  };
}
