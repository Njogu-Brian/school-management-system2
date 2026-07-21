import { ChartCard, useTheme } from '@erp/ui';
import { formatFinanceAmount } from '@erp/core';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';

export interface FinanceSummaryChartProps {
  collectedToday: number;
  collectedThisMonth: number;
  outstandingFees: number;
}

const ROWS = [
  { key: 'today', label: 'Collected today', field: 'collectedToday' as const },
  { key: 'month', label: 'Collected this month', field: 'collectedThisMonth' as const },
  { key: 'outstanding', label: 'Outstanding fees', field: 'outstandingFees' as const },
];

/** Currency progress bars — absolute KES values scaled to the max row (not a fake %). */
export const FinanceSummaryChart: React.FC<FinanceSummaryChartProps> = ({
  collectedToday,
  collectedThisMonth,
  outstandingFees,
}) => {
  const { palette, semantic, spacing, typography, radius } = useTheme();

  const data = { collectedToday, collectedThisMonth, outstandingFees };
  const max = Math.max(collectedToday, collectedThisMonth, outstandingFees, 1);
  const hasData = collectedToday > 0 || collectedThisMonth > 0 || outstandingFees > 0;
  if (!hasData) return null;

  return (
    <ChartCard
      title="Collections overview"
      subtitle="Absolute amounts in KES"
      accessibilityLabel="Finance collections chart showing absolute KES amounts"
    >
      <View style={{ gap: spacing.md }}>
        {ROWS.map((row) => {
          const amount = data[row.field];
          const barWidth = Math.min(100, Math.round((amount / max) * 100));
          return (
            <View key={row.key}>
              <View style={[styles.rowHeader, { marginBottom: spacing.xs }]}>
                <Text style={{ color: palette.textSub, fontSize: typography.caption.fontSize }}>
                  {row.label}
                </Text>
                <Text
                  style={{
                    color: palette.textMain,
                    fontSize: typography.body.fontSize,
                    fontWeight: '600',
                  }}
                >
                  {formatFinanceAmount(amount)}
                </Text>
              </View>
              <View
                style={[
                  styles.track,
                  { backgroundColor: palette.surfaceMuted, borderRadius: radius.full },
                ]}
              >
                <View
                  style={[
                    styles.fill,
                    {
                      width: `${barWidth}%`,
                      backgroundColor:
                        row.key === 'outstanding' ? semantic.warning.fg : palette.primary,
                      borderRadius: radius.full,
                    },
                  ]}
                />
              </View>
            </View>
          );
        })}
      </View>
    </ChartCard>
  );
};

const styles = StyleSheet.create({
  rowHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  track: { height: 8, overflow: 'hidden' },
  fill: { height: 8, minWidth: 4 },
});
