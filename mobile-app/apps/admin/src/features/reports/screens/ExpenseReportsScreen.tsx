import { useCan, useExpenseReportSummary } from '@erp/core';
import { AcademicScreenHeader, EmptyState, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { formatDateLabel, formatKes } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'ExpenseReports'>;

type Period = 'mtd' | 'qtd' | 'ytd';

function periodRange(period: Period): { from: string; to: string } {
  const now = new Date();
  const to = now.toISOString().slice(0, 10);
  if (period === 'mtd') {
    return { from: `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`, to };
  }
  if (period === 'qtd') {
    const qStart = new Date(now.getFullYear(), Math.floor(now.getMonth() / 3) * 3, 1);
    return { from: qStart.toISOString().slice(0, 10), to };
  }
  return { from: `${now.getFullYear()}-01-01`, to };
}

export const ExpenseReportsScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('reports.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const [period, setPeriod] = useState<Period>('mtd');
  const range = useMemo(() => periodRange(period), [period]);
  const query = useExpenseReportSummary({ enabled: canView, fromDate: range.from, toDate: range.to });

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const summary = query.data;

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
        <AcademicScreenHeader title="Expense reports" onBack={() => navigation.goBack()} />

        <View style={{ flexDirection: 'row', gap: 8, marginBottom: spacing.md }}>
          {(['mtd', 'qtd', 'ytd'] as Period[]).map((p) => (
            <Pressable
              key={p}
              onPress={() => setPeriod(p)}
              style={[styles.chip, period === p && { borderColor: colors.primary, backgroundColor: '#E8F0FA' }]}
            >
              <Text style={{ fontSize: fontSizes.xs, fontWeight: '600' }}>{p.toUpperCase()}</Text>
            </Pressable>
          ))}
        </View>

        {query.isLoading ? (
          <ActivityIndicator color={colors.primary} />
        ) : query.isError ? (
          <Pressable onPress={() => void query.refetch()}>
            <Text style={{ color: colors.error }}>Retry</Text>
          </Pressable>
        ) : summary ? (
          <>
            <FinanceFieldSection
              title="Summary"
              rows={[
                { label: 'Period', value: `${formatDateLabel(range.from)} – ${formatDateLabel(range.to)}` },
                { label: 'Total expenses', value: formatKes(summary.total_expenses) },
                { label: 'Expense count', value: String(summary.expense_count) },
              ]}
            />

            <Text style={{ fontWeight: '700', marginTop: spacing.lg, marginBottom: spacing.sm, color: palette.textPrimary }}>
              Category breakdown
            </Text>
            {summary.category_summary.length === 0 ? (
              <EmptyState title="No categories" message="No expenses in this period." icon="pie-chart-outline" />
            ) : (
              summary.category_summary.map((cat) => (
                <View key={cat.category_name} style={[styles.row, { borderColor: palette.border }]}>
                  <Text style={{ fontWeight: '600', color: palette.textPrimary }}>{cat.category_name}</Text>
                  <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>{formatKes(cat.total_amount)}</Text>
                </View>
              ))
            )}
          </>
        ) : null}

        <Pressable
          onPress={() => navigation.navigate('ExpensesList')}
          accessibilityRole="button"
          style={({ pressed }) => [
            styles.viewAllBtn,
            { borderColor: colors.primary, opacity: pressed ? 0.8 : 1 },
          ]}
        >
          <Text style={{ color: colors.primary, fontWeight: '700' }}>View all expenses</Text>
        </Pressable>

        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.lg }}>
          PDF and Excel export are available on the web portal.
        </Text>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  chip: { borderWidth: 1, borderColor: '#ccc', borderRadius: 16, paddingHorizontal: 12, paddingVertical: 8 },
  row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, padding: 12, marginBottom: 8 },
  viewAllBtn: {
    borderWidth: 1,
    borderRadius: 10,
    padding: 14,
    alignItems: 'center',
    marginTop: 20,
  },
});
