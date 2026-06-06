import { ChartCard, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { StyleSheet, useWindowDimensions } from 'react-native';
import { BarChart } from 'react-native-chart-kit';

export interface ExamBreakdownChartProps {
  breakdown: Record<string, number>;
}

/** Bar chart for exam status breakdown from academic dashboard data. */
export const ExamBreakdownChart: React.FC<ExamBreakdownChartProps> = ({ breakdown }) => {
  const { palette, spacing } = useTheme();
  const { width } = useWindowDimensions();
  const chartWidth = Math.min(width - spacing.md * 4, 360);

  const entries = Object.entries(breakdown).filter(([, count]) => count > 0);
  if (entries.length === 0) return null;

  const labels = entries.map(([status]) =>
    status.length > 8 ? `${status.slice(0, 7)}…` : status,
  );
  const values = entries.map(([, count]) => count);

  const chartConfig = useMemo(
    () => ({
      backgroundColor: palette.surfaceRaised,
      backgroundGradientFrom: palette.surfaceRaised,
      backgroundGradientTo: palette.surfaceRaised,
      decimalPlaces: 0,
      color: (opacity = 1) => `rgba(20, 184, 166, ${opacity})`,
      labelColor: () => palette.textSecondary,
      propsForBackgroundLines: { stroke: palette.borderSubtle, strokeWidth: 1 },
    }),
    [palette],
  );

  return (
    <ChartCard title="Exam pipeline" subtitle="Status breakdown">
      <BarChart
        data={{ labels, datasets: [{ data: values }] }}
        width={chartWidth}
        height={160}
        chartConfig={chartConfig}
        style={styles.chart}
        yAxisLabel=""
        yAxisSuffix=""
        fromZero
        showValuesOnTopOfBars
      />
    </ChartCard>
  );
};

const styles = StyleSheet.create({
  chart: { borderRadius: 12, marginLeft: -8 },
});
