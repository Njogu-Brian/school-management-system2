import React from 'react';
import { Profile360CompactBar } from '../layout/Profile360CompactBar';
import { Profile360Layout } from '../layout/Profile360Layout';
import { Staff360Header } from './Staff360Header';
import type { Staff360HeaderData, Staff360TabId } from './types';

export interface Staff360Tab {
  id: Staff360TabId;
  label: string;
}

export interface Staff360LayoutProps {
  header: Staff360HeaderData;
  tabs: Staff360Tab[];
  activeTab: Staff360TabId;
  onTabChange: (tab: Staff360TabId) => void;
  children: React.ReactNode;
}

/**
 * Staff 360 shell — collapsing header + ScrollableTabBar via Profile360Layout.
 * Back navigation relies on the stack / ScreenHeader; no custom Unicode back bar.
 */
export const Staff360Layout: React.FC<Staff360LayoutProps> = ({
  header,
  tabs,
  activeTab,
  onTabChange,
  children,
}) => (
  <Profile360Layout
    header={<Staff360Header staff={header} />}
    headerCompact={
      <Profile360CompactBar title={header.fullName} subtitle={header.orgLabel} />
    }
    tabs={tabs.map((t) => ({ key: t.id, label: t.label }))}
    activeTab={activeTab}
    onTabChange={onTabChange}
  >
    {children}
  </Profile360Layout>
);
