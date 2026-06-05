import { useCan } from '@erp/core';
import { ScreenContainer, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import {
  AlertsSection,
  CriticalKpisSection,
  OperationalStatusSection,
  PendingApprovalsSection,
  QuickActionsSection,
} from '../sections';

/**
 * School Command Center shell — composes permission-gated sections.
 * KPIs and pending approvals use live APIs (Sprint 2 Batches 2–3).
 */
type DashboardTab = 'overview' | 'approvals' | 'alerts';

export const DashboardLayout: React.FC = () => {
  const canViewDashboard = useCan('dashboard.view');
  const { palette, spacing, fontSizes, colors } = useTheme();
  const [tab, setTab] = useState<DashboardTab>('overview');

  if (!canViewDashboard) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={[styles.deniedText, { color: palette.textSecondary, fontSize: fontSizes.md }]}>
          You don&apos;t have permission to view the dashboard.
        </Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer contentContainerStyle={styles.content}>
      <View style={[styles.hero, { marginBottom: spacing.lg }]}>
        <Text style={[styles.heroTitle, { color: palette.textPrimary, fontSize: fontSizes.xl }]}>
          School Command Center
        </Text>
        <Text style={[styles.heroSub, { color: palette.textSecondary, fontSize: fontSizes.sm }]}>
          Overview for your branch
        </Text>
      </View>

      <View style={[styles.tabs, { marginBottom: spacing.md, gap: spacing.xs }]}>
        {([
          ['overview', 'Overview'],
          ['approvals', 'Approvals'],
          ['alerts', 'Alerts'],
        ] as const).map(([key, label]) => {
          const active = tab === key;
          return (
            <Pressable
              key={key}
              onPress={() => setTab(key)}
              style={[
                styles.tab,
                {
                  borderColor: active ? colors.primary : palette.border,
                  backgroundColor: active ? `${colors.primary}18` : 'transparent',
                },
              ]}
            >
              <Text style={{ color: active ? colors.primary : palette.textSecondary, fontWeight: '600', fontSize: fontSizes.sm }}>
                {label}
              </Text>
            </Pressable>
          );
        })}
      </View>

      {tab === 'overview' ? (
        <>
          <CriticalKpisSection />
          <QuickActionsSection />
          <OperationalStatusSection />
        </>
      ) : null}
      {tab === 'approvals' ? <PendingApprovalsSection /> : null}
      {tab === 'alerts' ? <AlertsSection /> : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  content: {
    paddingHorizontal: 16,
    paddingTop: 8,
    paddingBottom: 32,
  },
  denied: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  deniedText: { textAlign: 'center' },
  hero: {},
  heroTitle: { fontWeight: '700' },
  heroSub: { marginTop: 4 },
  tabs: { flexDirection: 'row' },
  tab: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 8,
    borderWidth: StyleSheet.hairlineWidth,
    alignItems: 'center',
  },
});
