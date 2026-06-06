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
  children: React.ReactNode;
}

export const Admissions360Layout: React.FC<Admissions360LayoutProps> = ({
  header,
  tabs,
  activeTab,
  onTabChange,
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
  >
    {children}
  </Profile360Layout>
);
