import React from 'react';
import { Profile360CompactBar } from '../layout/Profile360CompactBar';
import { Profile360Layout } from '../layout/Profile360Layout';
import { Student360Header } from './Student360Header';
import type { Student360HeaderData, Student360TabId } from './types';

export interface Student360Tab {
  id: Student360TabId;
  label: string;
}

export interface Student360LayoutProps {
  header: Student360HeaderData;
  tabs: Student360Tab[];
  activeTab: Student360TabId;
  onTabChange: (tab: Student360TabId) => void;
  /** Ionicons back via Profile360Layout — never Unicode arrows. */
  onBack?: () => void;
  children: React.ReactNode;
}

/**
 * Student 360 shell — collapsing header + ScrollableTabBar (minHeight 44) via Profile360Layout.
 */
export const Student360Layout: React.FC<Student360LayoutProps> = ({
  header,
  tabs,
  activeTab,
  onTabChange,
  onBack,
  children,
}) => (
  <Profile360Layout
    header={<Student360Header student={header} />}
    headerCompact={
      <Profile360CompactBar title={header.fullName} subtitle={header.admissionNumber} />
    }
    tabs={tabs.map((t) => ({ key: t.id, label: t.label }))}
    activeTab={activeTab}
    onTabChange={onTabChange}
    topBar={onBack ? { label: header.fullName, onBack } : undefined}
  >
    {children}
  </Profile360Layout>
);
