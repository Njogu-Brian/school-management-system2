import React from 'react';
import { ScrollableTabBar, type ScrollableTabBarProps } from '../layout/ScrollableTabBar';

export interface SegmentedTab<T extends string = string> {
  key: T;
  label: string;
}

export interface SegmentedTabBarProps<T extends string = string> {
  tabs: SegmentedTab<T>[];
  activeTab: T;
  onTabChange: (tab: T) => void;
  style?: ScrollableTabBarProps<T>['style'];
}

/** @deprecated Use ScrollableTabBar with variant="segmented". Kept for backward compatibility. */
export function SegmentedTabBar<T extends string>(props: SegmentedTabBarProps<T>) {
  return (
    <ScrollableTabBar
      tabs={props.tabs.map((t) => ({ key: t.key, label: t.label }))}
      activeTab={props.activeTab}
      onTabChange={props.onTabChange}
      variant="segmented"
      style={props.style}
    />
  );
}
