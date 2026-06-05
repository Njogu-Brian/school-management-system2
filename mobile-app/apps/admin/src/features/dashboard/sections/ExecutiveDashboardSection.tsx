import { useExecutiveAnalytics, type AnalyticsPeriod } from '@erp/core';
import { Button, KpiCard, WidgetGrid, WidgetShell, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import { Pressable, Share, StyleSheet, Text, View } from 'react-native';
import { formatKes } from '../../shared/utils/formatters';
import { ExecutiveCharts } from '../components/ExecutiveCharts';

const PERIODS: AnalyticsPeriod[] = ['week', 'month', 'term', 'year'];

export const ExecutiveDashboardSection: React.FC = () => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const [period, setPeriod] = useState<AnalyticsPeriod>('month');
  const analyticsQuery = useExecutiveAnalytics(period);

  const state = analyticsQuery.isLoading ? 'loading' : analyticsQuery.isError ? 'error' : 'success';
  const data = analyticsQuery.data;

  const shareReport = async () => {
    if (!data) {
      return;
    }
    const body = [
      `Royal Kings ERP — Executive summary (${period})`,
      `Collected: ${formatKes(data.finance.monthly_collections)}`,
      `Outstanding: ${formatKes(data.finance.outstanding_balances)}`,
      `Inventory alerts: ${data.operations.inventory_alerts}`,
      `Assets: ${data.operations.assets}`,
    ].join('\n');
    await Share.share({ message: body, title: 'Executive analytics' });
  };

  return (
    <View>
      <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: spacing.sm }}>
        <Text style={{ fontWeight: '700', color: palette.textPrimary }}>Executive analytics</Text>
        <Button label="Share" variant="ghost" onPress={() => void shareReport()} />
      </View>

      <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginBottom: spacing.md }}>
        {PERIODS.map((p) => (
          <Pressable
            key={p}
            onPress={() => setPeriod(p)}
            style={[styles.chip, period === p && { borderColor: colors.primary, backgroundColor: '#E8F0FA' }]}
          >
            <Text style={{ fontSize: fontSizes.xs, textTransform: 'capitalize' }}>{p}</Text>
          </Pressable>
        ))}
      </View>

      <WidgetGrid>
        <WidgetShell state={state} title="Finance" onRetry={() => void analyticsQuery.refetch()}>
          <KpiCard
            label="Collected (period)"
            value={formatKes(data?.finance.monthly_collections)}
            icon="cash-outline"
          />
        </WidgetShell>
        <WidgetShell state={state} title="Outstanding">
          <KpiCard
            label="Outstanding"
            value={formatKes(data?.finance.outstanding_balances)}
            icon="wallet-outline"
          />
        </WidgetShell>
        <WidgetShell state={state} title="Operations">
          <KpiCard
            label="Inventory alerts"
            value={String(data?.operations.inventory_alerts ?? '—')}
            icon="warning-outline"
          />
        </WidgetShell>
        <WidgetShell state={state} title="Assets">
          <KpiCard label="Fixed assets" value={String(data?.operations.assets ?? '—')} icon="cube-outline" />
        </WidgetShell>
      </WidgetGrid>

      {data ? (
        <View style={{ marginTop: spacing.md }}>
          <ExecutiveCharts
            collections={data.finance.daily_collections}
            enrollmentPie={data.admissions.enrollment_pie}
            attendance={data.academics.attendance_trends}
          />
        </View>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  chip: { borderWidth: 1, borderColor: '#ccc', borderRadius: 14, paddingHorizontal: 10, paddingVertical: 6 },
});
