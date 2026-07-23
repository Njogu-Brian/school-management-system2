import { formatFinanceAmount, useCan, useFinanceDashboardKpis } from '@erp/core';
import {
  DashboardHero,
  DashboardSection,
  KpiCard,
  QuickAction,
  ScreenContainer,
  WidgetGrid,
  WidgetShell,
  useFloatingTabBarClearance,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useCallback } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { FinanceStackParamList } from '../../../navigation/financeStackTypes';
import { navigateToDrawer } from '../../../navigation/navigateWorkspace';
import { FinanceSummaryChart } from '../components/FinanceSummaryChart';

const KPI_CONFIG = [
  { key: 'collectedToday' as const, label: 'Collected Today', icon: 'today-outline' as const },
  { key: 'collectedThisMonth' as const, label: 'Collected This Month', icon: 'calendar-outline' as const },
  { key: 'outstandingFees' as const, label: 'Outstanding Fees', icon: 'wallet-outline' as const },
  { key: 'studentsInArrears' as const, label: 'Students In Arrears', icon: 'alert-circle-outline' as const },
];

const SECTIONS = [
  { route: 'BillingList' as const, label: 'Billing', icon: 'receipt-outline' as const, subtitle: 'Invoices & fee structures' },
  { route: 'FeeBalances' as const, label: 'Fee balances', icon: 'alert-circle-outline' as const, subtitle: 'Students in arrears' },
  { route: 'CollectionsList' as const, label: 'Collections', icon: 'cash-outline' as const, subtitle: 'Payments & transactions' },
  { route: 'Statements' as const, label: 'Statements', icon: 'document-text-outline' as const, subtitle: 'Student fee statements' },
];

export const FinanceDashboardScreen: React.FC = () => {
  const canView = useCan('finance.view');
  const canReports = useCan('reports.view');
  const navigation = useNavigation<StackNavigationProp<FinanceStackParamList>>();
  const { colors, palette, spacing, typography } = useTheme();
  const tabClearance = useFloatingTabBarClearance();
  const kpisQuery = useFinanceDashboardKpis({ enabled: canView });

  const openSection = useCallback(
    (route: (typeof SECTIONS)[number]['route']) => {
      navigation.navigate(route);
    },
    [navigation],
  );

  const openKpi = useCallback(
    (key: (typeof KPI_CONFIG)[number]['key']) => {
      if (key === 'studentsInArrears') {
        navigation.navigate('FeeBalances');
        return;
      }
      if (key === 'outstandingFees') {
        navigation.navigate('FeeBalances');
        return;
      }
      if (key === 'collectedToday' || key === 'collectedThisMonth') {
        navigation.navigate('CollectionsList', { initialTab: 'payments' });
      }
    },
    [navigation],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize, textAlign: 'center' }}>
          You need finance.view permission to open the finance workspace.
        </Text>
      </ScreenContainer>
    );
  }

  const kpiData = kpisQuery.data;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: tabClearance }}
        refreshControl={
          <RefreshControl
            refreshing={kpisQuery.isRefetching}
            onRefresh={() => void kpisQuery.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
      >
        <DashboardHero
          variant="finance"
          title="Finance Dashboard"
          subtitle="Collections, billing & statements"
          meta={
            kpiData
              ? `${formatFinanceAmount(kpiData.collectedThisMonth ?? 0)} collected this month`
              : undefined
          }
        />

        <WidgetGrid>
          {KPI_CONFIG.map((kpi) => {
            const raw = kpiData?.[kpi.key];
            const value =
              kpi.key === 'studentsInArrears'
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
                <KpiCard
                  label={kpi.label}
                  value={value}
                  icon={kpi.icon}
                  onPress={() => openKpi(kpi.key)}
                />
              </WidgetShell>
            );
          })}
        </WidgetGrid>

        {kpiData ? (
          <View style={{ marginTop: spacing.md }}>
            <FinanceSummaryChart
              collectedToday={kpiData.collectedToday ?? 0}
              collectedThisMonth={kpiData.collectedThisMonth ?? 0}
              outstandingFees={kpiData.outstandingFees ?? 0}
            />
          </View>
        ) : null}

        {kpisQuery.isError ? (
          <Pressable onPress={() => void kpisQuery.refetch()} style={{ marginTop: spacing.sm }}>
            <Text style={{ color: colors.error, textAlign: 'center' }}>
              {(kpisQuery.error as Error).message}
            </Text>
          </Pressable>
        ) : null}

        <DashboardSection title="Workspace">
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
        </DashboardSection>

        {canReports ? (
          <DashboardSection title="Accounting & reporting">
            <View style={[styles.actions, { gap: spacing.sm }]}>
              <QuickAction
                label="Expense reports"
                icon="pie-chart-outline"
                onPress={() => navigateToDrawer(navigation, 'Reports', 'ExpenseReports')}
              />
              <QuickAction
                label="All expenses"
                icon="wallet-outline"
                onPress={() => navigateToDrawer(navigation, 'Reports', 'ExpensesList')}
              />
              <QuickAction
                label="Income statement"
                icon="stats-chart-outline"
                onPress={() => navigateToDrawer(navigation, 'Reports', 'IncomeStatement')}
              />
              <QuickAction
                label="Balance sheet"
                icon="scale-outline"
                onPress={() => navigateToDrawer(navigation, 'Reports', 'BalanceSheet')}
              />
              <QuickAction
                label="General ledger"
                icon="book-outline"
                onPress={() => navigateToDrawer(navigation, 'Reports', 'Ledger')}
              />
              <QuickAction
                label="Executive analytics"
                icon="trending-up-outline"
                onPress={() => navigateToDrawer(navigation, 'Reports', 'ExecutiveAnalytics')}
              />
              <QuickAction
                label="Board pack"
                icon="briefcase-outline"
                onPress={() => navigateToDrawer(navigation, 'Reports', 'BoardPack')}
              />
            </View>
          </DashboardSection>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  actions: { flexDirection: 'row', flexWrap: 'wrap' },
});
