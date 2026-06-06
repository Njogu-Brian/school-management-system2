import { ChartCard, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { StyleSheet, useWindowDimensions } from 'react-native';
import { BarChart } from 'react-native-chart-kit';

export interface FinanceSummaryChartProps {
  collectedToday: number;
  collectedThisMonth: number;
  outstandingFees: number;
}

/** Bar chart comparing key finance KPIs — visual only, no API changes. */
export const FinanceSummaryChart: React.FC<FinanceSummaryChartProps> = ({
  collectedToday,
  collectedThisMonth,
  outstandingFees,
}) => {
  const { palette, spacing } = useTheme();
  const { width } = useWindowDimensions();
  const chartWidth = Math.min(width - spacing.md * 4, 360);

  const chartConfig = useMemo(
    () => ({
      backgroundColor: palette.surfaceRaised,
      backgroundGradientFrom: palette.surfaceRaised,
      backgroundGradientTo: palette.surfaceRaised,
      decimalPlaces: 0,
      color: (opacity = 1) => `rgba(0, 74, 153, ${opacity})`,
      labelColor: () => palette.textSecondary,
      propsForBackgroundLines: { stroke: palette.borderSubtle, strokeWidth: 1 },
    }),
    [palette],
  );

  const hasData = collectedToday > 0 || collectedThisMonth > 0 || outstandingFees > 0;
  if (!hasData) return null;

  const scale = Math.max(collectedThisMonth, outstandingFees, collectedToday, 1);

  return (
    <ChartCard title="Collections overview" subtitle="Today · This month · Outstanding">
      <BarChart
        data={{
          labels: ['Today', 'Month', 'Outstanding'],
          datasets: [
            {
              data: [
                Math.round((collectedToday / scale) * 100) || 0,
                Math.round((collectedThisMonth / scale) * 100) || 0,
                Math.round((outstandingFees / scale) * 100) || 0,
              ],
            },
          ],
        }}
        width={chartWidth}
        height={160}
        chartConfig={chartConfig}
        style={styles.chart}
        yAxisLabel=""
        yAxisSuffix="%"
        fromZero
        showValuesOnTopOfBars
      />
    </ChartCard>
  );
};

const styles = StyleSheet.create({
  chart: { borderRadius: 12, marginLeft: -8 },
});
