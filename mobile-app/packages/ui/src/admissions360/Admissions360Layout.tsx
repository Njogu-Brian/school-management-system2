import React from 'react';
import { Profile360CompactBar } from '../layout/Profile360CompactBar';
import { Profile360Layout } from '../layout/Profile360Layout';
import { Admissions360Header } from './Admissions360Header';
import type { Admissions360HeaderData, Admissions360TabId } from './types';

export interface Admissions360Tab {
  id: Admissions360TabId;
  label: string;
}

export interface Admissions360LayoutProps {
  header: Admissions360HeaderData;
  tabs: Admissions360Tab[];
  activeTab: Admissions360TabId;
  onTabChange: (tab: Admissions360TabId) => void;
  /** Ionicons back via Profile360Layout — never Unicode arrows. */
  onBack?: () => void;
  children: React.ReactNode;
}

/**
 * Admissions 360 shell — collapsing header + ScrollableTabBar (minHeight 44) via Profile360Layout.
 */
export const Admissions360Layout: React.FC<Admissions360LayoutProps> = ({
  header,
  tabs,
  activeTab,
  onTabChange,
  onBack,
  children,
}) => (
  <Profile360Layout
    header={<Admissions360Header application={header} />}
    headerCompact={
      <Profile360CompactBar
        title={header.fullName}
        subtitle={header.preferredClassName ?? header.applicationStatus}
      />
    }
    tabs={tabs.map((t) => ({ key: t.id, label: t.label }))}
    activeTab={activeTab}
    onTabChange={onTabChange}
    topBar={onBack ? { label: header.fullName, onBack } : undefined}
  >
    {children}
  </Profile360Layout>
);
