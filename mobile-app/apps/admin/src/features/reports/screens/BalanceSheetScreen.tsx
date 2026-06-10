import { useBalanceSheet, useCan, useTrialBalance } from '@erp/core';
import {
  AcademicScreenHeader,
  FinanceFieldSection,
  KpiCard,
  ListEmptyState,
  ScreenContainer,
  SkeletonWidgetGrid,
  WidgetGrid,
  WidgetShell,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { formatKes } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'BalanceSheet'>;

export const BalanceSheetScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('reports.view');
  const { colors, palette, spacing, typography } = useTheme();
  const query = useBalanceSheet({ enabled: canView });
  const trialQuery = useTrialBalance({ enabled: canView });

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const data = query.data;
  const state = query.isLoading ? 'loading' : query.isError ? 'error' : 'success';
  const netPositive = (data?.totals.net_position ?? 0) >= 0;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={query.isRefetching || trialQuery.isRefetching}
            onRefresh={() => {
              void query.refetch();
              void trialQuery.refetch();
            }}
            colors={[colors.primary]}
          />
        }
      >
        <AcademicScreenHeader
          title="Balance sheet"
          subtitle="Financial position snapshot"
          onBack={() => navigation.goBack()}
        />

        {query.isLoading ? (
          <SkeletonWidgetGrid count={3} />
        ) : query.isError ? (
          <ListEmptyState
            title="Could not load balance sheet"
            message={(query.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void query.refetch()}
          />
        ) : data ? (
          <>
            <WidgetGrid>
              <WidgetShell state={state} title="Assets">
                <KpiCard label="Total assets" value={formatKes(data.totals.assets)} icon="business-outline" />
              </WidgetShell>
              <WidgetShell state={state} title="Liabilities">
                <KpiCard label="Total liabilities" value={formatKes(data.totals.liabilities)} icon="card-outline" />
              </WidgetShell>
              <WidgetShell state={state} title="Net position">
                <KpiCard
                  label={netPositive ? 'Net position' : 'Net deficit'}
                  value={formatKes(Math.abs(data.totals.net_position))}
                  icon={netPositive ? 'checkmark-circle-outline' : 'warning-outline'}
                />
              </WidgetShell>
            </WidgetGrid>

            <FinanceFieldSection
              title="Assets"
              rows={data.assets.map((a) => ({ label: a.label, value: formatKes(a.amount) }))}
            />
            <FinanceFieldSection
              title="Liabilities"
              rows={data.liabilities.map((l) => ({ label: l.label, value: formatKes(l.amount) }))}
            />

            {trialQuery.data && trialQuery.data.accounts.length > 0 ? (
              <View style={{ marginTop: spacing.md }}>
                <FinanceFieldSection
                  title="Trial balance (posted ledger)"
                  rows={[
                    ...trialQuery.data.accounts.map((acc) => ({
                      label: acc.account_code,
                      value: `DR ${formatKes(acc.total_dr)} · CR ${formatKes(acc.total_cr)}`,
                    })),
                    {
                      label: 'Totals',
                      value: `DR ${formatKes(trialQuery.data.totals.dr)} · CR ${formatKes(trialQuery.data.totals.cr)}`,
                    },
                  ]}
                />
              </View>
            ) : null}

            <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: spacing.md }}>
              Derived snapshot: cash = collections minus expense payments; receivables = outstanding
              invoices; payables = approved unpaid expenses. Fixed assets and inventory are at cost.
            </Text>
          </>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
