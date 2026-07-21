import { useBoardPack, useCan } from '@erp/core';
import { AcademicScreenHeader, FinanceFieldSection, ScreenContainer, WidgetGrid, WidgetShell, KpiCard, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { formatDateTimeLabel, formatKes } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'BoardPack'>;

export const BoardPackScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('reports.view');
  const { colors, palette, spacing, typography } = useTheme();
  const query = useBoardPack({ enabled: canView });

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const pack = query.data;
  const finance = (pack?.finance ?? {}) as Record<string, number | string | undefined>;
  const operations = (pack?.operations ?? {}) as Record<string, Record<string, number> | string | undefined>;

  const state = query.isLoading ? 'loading' : query.isError ? 'error' : 'success';

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={query.isRefetching}
            onRefresh={() => void query.refetch()}
            colors={[colors.primary]}
          />
        }
      >
        <AcademicScreenHeader title="Board pack" onBack={() => navigation.goBack()} />

        {query.isError ? (
          <Pressable onPress={() => void query.refetch()}>
            <Text style={{ color: colors.error, textAlign: 'center' }}>Retry</Text>
          </Pressable>
        ) : null}

        <WidgetShell state={state} title="Executive" onRetry={() => void query.refetch()}>
          <WidgetGrid>
            <KpiCard label="Pending approvals" value={String(pack?.approvals.pending_total ?? '—')} icon="checkmark-circle-outline" />
            <KpiCard label="Expenses MTD" value={formatKes(pack?.expenses.month_to_date)} icon="receipt-outline" />
            <KpiCard label="Open issues" value={String(pack?.facilities.open_issues ?? '—')} icon="warning-outline" />
            <KpiCard label="Low stock" value={String(pack?.facilities.low_stock_items ?? '—')} icon="cube-outline" />
          </WidgetGrid>
        </WidgetShell>

        <Text style={{ fontWeight: '700', color: palette.textPrimary, marginTop: spacing.lg, marginBottom: spacing.sm }}>
          Finance summary
        </Text>
        <FinanceFieldSection
          title="Finance"
          rows={[
            { label: 'Collected today', value: formatKes(finance.collected_today as number) },
            { label: 'Collected this month', value: formatKes(finance.collected_this_month as number) },
            { label: 'Outstanding', value: formatKes(finance.outstanding_balance as number) },
            { label: 'Students in arrears', value: String(finance.students_in_arrears ?? '—') },
          ]}
        />

        <Text style={{ fontWeight: '700', color: palette.textPrimary, marginTop: spacing.lg, marginBottom: spacing.sm }}>
          Operations summary
        </Text>
        <FinanceFieldSection
          title="Operations"
          rows={[
            { label: 'Active trips', value: String((operations.transport as Record<string, number>)?.active_trips ?? '—') },
            { label: 'Low stock items', value: String((operations.inventory as Record<string, number>)?.low_stock_items ?? '—') },
            { label: 'Visitors on site', value: String((operations.visitors as Record<string, number>)?.on_site ?? '—') },
            { label: 'Active assets', value: String((operations.assets as Record<string, number>)?.active ?? '—') },
          ]}
        />

        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: spacing.md }}>
          Generated {formatDateTimeLabel(pack?.generated_at)}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: spacing.sm }}>
          PDF export and print are available on the web portal.
        </Text>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
