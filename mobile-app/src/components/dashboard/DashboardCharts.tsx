import React, { useMemo } from 'react';
import { View, Text, StyleSheet, useWindowDimensions } from 'react-native';
import { LineChart, BarChart } from 'react-native-chart-kit';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { BRAND } from '@constants/designTokens';

function hexToRgb(hex: string): { r: number; g: number; b: number } {
    const h = hex.replace('#', '');
    const full = h.length === 3 ? h.split('').map((c) => c + c).join('') : h;
    const n = parseInt(full, 16);
    return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
}

function rgba(hex: string, opacity: number): string {
    const { r, g, b } = hexToRgb(hex);
    return `rgba(${r},${g},${b},${opacity})`;
}

interface DashboardLineChartProps {
    title: string;
    labels: string[];
    data: number[];
    height?: number;
}

export const DashboardLineChart: React.FC<DashboardLineChartProps> = ({
    title,
    labels,
    data,
    height = 200,
}) => {
    const { isDark, colors } = useTheme();
    const { width: winW } = useWindowDimensions();
    const chartWidth = Math.min(winW - SPACING.xl * 2, 400);

    const chartConfig = useMemo(
        () => ({
            backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
            backgroundGradientFrom: isDark ? colors.surfaceDark : BRAND.surface,
            backgroundGradientTo: isDark ? colors.surfaceDark : BRAND.surface,
            decimalPlaces: 0,
            color: (opacity = 1) => rgba(colors.primary, opacity),
            labelColor: (opacity = 1) =>
                isDark ? `rgba(248,250,252,${opacity})` : `rgba(15,23,42,${opacity})`,
            propsForDots: {
                r: '4',
                strokeWidth: '2',
                stroke: colors.primary,
            },
            propsForBackgroundLines: {
                strokeDasharray: '',
                stroke: isDark ? colors.borderDark : BRAND.border,
            },
        }),
        [colors.primary, colors.surfaceDark, isDark]
    );

    return (
        <View
            style={[
                styles.card,
                {
                    backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                    borderColor: isDark ? colors.borderDark : BRAND.border,
                },
            ]}
        >
            <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>{title}</Text>
            <LineChart
                data={{
                    labels,
                    datasets: [{ data: data.length ? data : [0] }],
                }}
                width={chartWidth}
                height={height}
                chartConfig={chartConfig}
                bezier
                style={styles.chart}
                withInnerLines
                withOuterLines
                withVerticalLabels
                withHorizontalLabels
            />
        </View>
    );
};

interface DashboardBarChartProps {
    title: string;
    labels: string[];
    data: number[];
    height?: number;
}

export const DashboardBarChart: React.FC<DashboardBarChartProps> = ({
    title,
    labels,
    data,
    height = 200,
}) => {
    const { isDark, colors } = useTheme();
    const { width: winW } = useWindowDimensions();
    const chartWidth = Math.min(winW - SPACING.xl * 2, 400);

    const chartConfig = useMemo(
        () => ({
            backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
            backgroundGradientFrom: isDark ? colors.surfaceDark : BRAND.surface,
            backgroundGradientTo: isDark ? colors.surfaceDark : BRAND.surface,
            decimalPlaces: 0,
            color: (opacity = 1) => rgba(colors.info, opacity),
            labelColor: (opacity = 1) =>
                isDark ? `rgba(248,250,252,${opacity})` : `rgba(15,23,42,${opacity})`,
        }),
        [colors.info, colors.surfaceDark, isDark]
    );

    return (
        <View
            style={[
                styles.card,
                {
                    backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                    borderColor: isDark ? colors.borderDark : BRAND.border,
                },
            ]}
        >
            <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>{title}</Text>
            <BarChart
                data={{
                    labels,
                    datasets: [{ data: data.length ? data : [0] }],
                }}
                width={chartWidth}
                height={height}
                chartConfig={chartConfig}
                style={styles.chart}
                fromZero
                showValuesOnTopOfBars
                yAxisLabel=""
                yAxisSuffix=""
            />
        </View>
    );
};

const styles = StyleSheet.create({
    card: {
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        padding: SPACING.md,
        marginBottom: SPACING.md,
        overflow: 'hidden',
    },
    title: {
        fontSize: FONT_SIZES.md,
        fontWeight: '700',
        marginBottom: SPACING.sm,
        paddingHorizontal: SPACING.xs,
    },
    chart: {
        marginVertical: SPACING.xs,
        borderRadius: BORDER_RADIUS.lg,
        marginLeft: -SPACING.sm,
    },
});
