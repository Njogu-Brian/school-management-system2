import { ChartCard, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { StyleSheet, useWindowDimensions } from 'react-native';
import { BarChart, LineChart, PieChart } from 'react-native-chart-kit';

interface ChartSeries {
  labels: string[];
  values: number[];
}

interface PieSlice {
  name: string;
  value: number;
  color: string;
}

interface ExecutiveChartsProps {
  collections: ChartSeries;
  enrollmentPie: PieSlice[];
  attendance: ChartSeries;
}

export const ExecutiveCharts: React.FC<ExecutiveChartsProps> = ({
  collections,
  enrollmentPie,
  attendance,
}) => {
  const { colors, palette, spacing } = useTheme();
  const { width } = useWindowDimensions();
  const chartWidth = Math.min(width - spacing.md * 2, 380);

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
      propsForDots: { r: '4', strokeWidth: '2', stroke: colors.primary },
      propsForBackgroundLines: { strokeDasharray: '', stroke: palette.borderSubtle, strokeWidth: 1 },
    }),
    [colors.primary, palette],
  );

  const pieData = enrollmentPie.filter((s) => s.value > 0);

  return (
    <>
      <ChartCard title="Fee collections" subtitle="Trend over selected period">
        <LineChart
          data={{
            labels: collections.labels,
            datasets: [{ data: collections.values.length ? collections.values : [0] }],
          }}
          width={chartWidth}
          height={180}
          chartConfig={chartConfig}
          bezier
          style={styles.chart}
          withInnerLines
          withOuterLines={false}
        />
      </ChartCard>

      <ChartCard title="Attendance %" subtitle="Daily attendance rate">
        <BarChart
          data={{
            labels: attendance.labels,
            datasets: [{ data: attendance.values.length ? attendance.values : [0] }],
          }}
          width={chartWidth}
          height={180}
          chartConfig={chartConfig}
          style={styles.chart}
          yAxisLabel=""
          yAxisSuffix="%"
          fromZero
          showValuesOnTopOfBars
        />
      </ChartCard>

      {pieData.length > 0 ? (
        <ChartCard title="Enrollment mix" subtitle="Distribution by category">
          <PieChart
            data={pieData.map((s) => ({
              name: s.name,
              population: s.value,
              color: s.color,
              legendFontColor: palette.textSecondary,
              legendFontSize: 12,
            }))}
            width={chartWidth}
            height={180}
            chartConfig={chartConfig}
            accessor="population"
            backgroundColor="transparent"
            paddingLeft="8"
            absolute
          />
        </ChartCard>
      ) : null}
    </>
  );
};

const styles = StyleSheet.create({
  chart: { borderRadius: 12, marginLeft: -8 },
});
