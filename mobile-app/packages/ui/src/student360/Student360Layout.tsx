import React from 'react';
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
  children: React.ReactNode;
}

export const Student360Layout: React.FC<Student360LayoutProps> = ({
  header,
  tabs,
  activeTab,
  onTabChange,
  children,
}) => (
  <Profile360Layout
    header={<Student360Header student={header} />}
    tabs={tabs.map((t) => ({ key: t.id, label: t.label }))}
    activeTab={activeTab}
    onTabChange={onTabChange}
  >
    {children}
  </Profile360Layout>
);
