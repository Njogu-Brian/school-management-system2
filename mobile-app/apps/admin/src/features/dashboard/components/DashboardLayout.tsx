import { useCan } from '@erp/core';
import { ScreenContainer, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
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
    <View style={{ flex: 1 }}>
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
            ['executive', 'Executive'],
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
                <Text style={{ color: active ? colors.primary : palette.textSecondary, fontWeight: '600', fontSize: fontSizes.xs }}>
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
  hero: {},
  heroTitle: { fontWeight: '700' },
  heroSub: { marginTop: 4 },
  tabs: { flexDirection: 'row', flexWrap: 'wrap' },
  tab: {
    flexGrow: 1,
    minWidth: '22%',
    paddingVertical: 10,
    borderRadius: 8,
    borderWidth: StyleSheet.hairlineWidth,
    alignItems: 'center',
  },
});
