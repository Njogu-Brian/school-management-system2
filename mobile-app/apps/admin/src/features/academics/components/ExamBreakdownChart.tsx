import { ChartCard, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { useWindowDimensions } from 'react-native';
import { BarChart } from 'react-native-chart-kit';

export interface ExamBreakdownChartProps {
  breakdown: Record<string, number>;
}

/** Bar chart for exam status breakdown from academic dashboard data. */
export const ExamBreakdownChart: React.FC<ExamBreakdownChartProps> = ({ breakdown }) => {
  const { palette, spacing, colors, radius } = useTheme();
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
      color: (opacity = 1) => {
        const hex = colors.primary.replace('#', '');
        const r = parseInt(hex.slice(0, 2), 16);
        const g = parseInt(hex.slice(2, 4), 16);
        const b = parseInt(hex.slice(4, 6), 16);
        return `rgba(${r}, ${g}, ${b}, ${opacity})`;
      },
      labelColor: () => palette.textSecondary,
      propsForBackgroundLines: { stroke: palette.borderSubtle, strokeWidth: 1 },
    }),
    [palette, colors.primary],
  );

  return (
    <ChartCard
      title="Exam pipeline"
      subtitle="Status breakdown"
      accessibilityLabel="Exam pipeline chart showing status breakdown counts"
    >
      <BarChart
        data={{ labels, datasets: [{ data: values }] }}
        width={chartWidth}
        height={160}
        chartConfig={chartConfig}
        style={{ borderRadius: radius.lg, marginLeft: -spacing.sm }}
        yAxisLabel=""
        yAxisSuffix=""
        fromZero
        showValuesOnTopOfBars
      />
    </ChartCard>
  );
};
