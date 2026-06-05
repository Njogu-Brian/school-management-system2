import { formatFinanceAmount, useCan, useFinanceDashboardKpis } from '@erp/core';
import {
  KpiCard,
  QuickAction,
  ScreenContainer,
  WidgetGrid,
  WidgetShell,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useCallback } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { FinanceStackParamList } from '../../../navigation/financeStackTypes';

const KPI_CONFIG = [
  { key: 'collectedToday' as const, label: 'Collected Today', icon: 'today-outline' as const },
  { key: 'collectedThisMonth' as const, label: 'Collected This Month', icon: 'calendar-outline' as const },
  { key: 'outstandingFees' as const, label: 'Outstanding Fees', icon: 'wallet-outline' as const },
  { key: 'studentsInArrears' as const, label: 'Students In Arrears', icon: 'alert-circle-outline' as const },
  { key: 'pendingReconciliation' as const, label: 'Pending Reconciliation', icon: 'git-compare-outline' as const },
];

const SECTIONS = [
  { route: 'BillingList' as const, label: 'Billing', icon: 'receipt-outline' as const, subtitle: 'Invoices & fee structures' },
  { route: 'CollectionsList' as const, label: 'Collections', icon: 'cash-outline' as const, subtitle: 'Payments & receipts' },
  { route: 'Statements' as const, label: 'Statements', icon: 'document-text-outline' as const, subtitle: 'Student fee statements' },
  { route: 'ReconciliationList' as const, label: 'Reconciliation', icon: 'swap-horizontal-outline' as const, subtitle: 'Bank & M-Pesa queue' },
];

export const FinanceDashboardScreen: React.FC = () => {
  const canView = useCan('finance.view');
  const navigation = useNavigation<StackNavigationProp<FinanceStackParamList>>();
  const { colors, palette, spacing, fontSizes } = useTheme();
  const kpisQuery = useFinanceDashboardKpis({ enabled: canView });

  const openSection = useCallback(
    (route: (typeof SECTIONS)[number]['route']) => {
      navigation.navigate(route);
    },
    [navigation],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.md, textAlign: 'center' }}>
          You need finance.view permission to open the finance workspace.
        </Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={kpisQuery.isRefetching}
            onRefresh={() => void kpisQuery.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
      >
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.lg, fontWeight: '700', marginBottom: spacing.sm }}>
          Finance Dashboard
        </Text>

        <WidgetGrid>
          {KPI_CONFIG.map((kpi) => {
            const raw = kpisQuery.data?.[kpi.key];
            const value =
              kpi.key === 'studentsInArrears' || kpi.key === 'pendingReconciliation'
                ? String(raw ?? 0)
                : formatFinanceAmount(typeof raw === 'number' ? raw : 0);
            const state = kpisQuery.isLoading
              ? 'loading'
              : kpisQuery.isError
                ? 'error'
                : raw == null
                  ? 'empty'
                  : 'success';
            return (
              <WidgetShell
                key={kpi.key}
                state={state}
                title={kpi.label}
                onRetry={() => void kpisQuery.refetch()}
              >
                <KpiCard label={kpi.label} value={value} icon={kpi.icon} />
              </WidgetShell>
            );
          })}
        </WidgetGrid>

        {kpisQuery.isError ? (
          <Pressable onPress={() => void kpisQuery.refetch()} style={{ marginTop: spacing.sm }}>
            <Text style={{ color: colors.error, textAlign: 'center' }}>
              {(kpisQuery.error as Error).message}
            </Text>
          </Pressable>
        ) : null}

        <Text
          style={{
            color: palette.textPrimary,
            fontSize: fontSizes.md,
            fontWeight: '700',
            marginTop: spacing.lg,
            marginBottom: spacing.sm,
          }}
        >
          Workspace
        </Text>

        <View style={[styles.actions, { gap: spacing.sm }]}>
          {SECTIONS.map((section) => (
            <QuickAction
              key={section.route}
              label={section.label}
              icon={section.icon}
              onPress={() => openSection(section.route)}
            />
          ))}
        </View>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  actions: { flexDirection: 'row', flexWrap: 'wrap' },
});
