import { useCan } from '@erp/core';
import {
  DashboardHero,
  ScrollableTabBar,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import React, { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import {
  AlertsSection,
  CriticalKpisSection,
  ExecutiveDashboardSection,
  OperationalStatusSection,
  PendingApprovalsSection,
  QuickActionsSection,
} from '../sections';
import { QuickActionFab } from './QuickActionFab';

type DashboardTab = 'overview' | 'executive' | 'approvals' | 'alerts';

const DASHBOARD_TABS = [
  { key: 'overview' as const, label: 'Overview' },
  { key: 'executive' as const, label: 'Executive' },
  { key: 'approvals' as const, label: 'Approvals' },
  { key: 'alerts' as const, label: 'Alerts' },
];

export const DashboardLayout: React.FC = () => {
  const canViewDashboard = useCan('dashboard.view');
  const { palette, typography } = useTheme();
  const [tab, setTab] = useState<DashboardTab>('overview');

  if (!canViewDashboard) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text
          style={[
            styles.deniedText,
            { color: palette.textSecondary, fontSize: typography.body.fontSize },
          ]}
        >
          You don&apos;t have permission to view the dashboard.
        </Text>
      </ScreenContainer>
    );
  }

  return (
    <View style={{ flex: 1 }}>
      <ScreenContainer contentContainerStyle={styles.content}>
        <DashboardHero
          variant="default"
          title="School Command Center"
          subtitle="Overview for your branch"
          meta="Real-time KPIs · Approvals · Alerts"
        />

        <ScrollableTabBar
          variant="segmented"
          tabs={DASHBOARD_TABS}
          activeTab={tab}
          onTabChange={setTab}
        />

        {tab === 'overview' ? (
          <>
            <CriticalKpisSection />
            <QuickActionsSection />
            <OperationalStatusSection />
          </>
        ) : null}
        {tab === 'executive' ? <ExecutiveDashboardSection /> : null}
        {tab === 'approvals' ? <PendingApprovalsSection /> : null}
        {tab === 'alerts' ? <AlertsSection /> : null}
      </ScreenContainer>
      <QuickActionFab />
    </View>
  );
};

const styles = StyleSheet.create({
  content: {
    paddingHorizontal: 16,
    paddingTop: 8,
    paddingBottom: 88,
  },
  denied: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  deniedText: { textAlign: 'center' },
});
