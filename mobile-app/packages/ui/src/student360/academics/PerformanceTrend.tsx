import React from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../../theme/ThemeContext';
import type { PerformanceTrendPointData } from './types';

export interface PerformanceTrendProps {
  title?: string;
  points: PerformanceTrendPointData[];
  emptyMessage?: string;
}

export const PerformanceTrend: React.FC<PerformanceTrendProps> = ({
  title = 'Performance trend',
  points,
  emptyMessage = 'Not enough data for a trend yet.',
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();
  const max = Math.max(...points.map((p) => p.percentage), 1);

  return (
    <View style={{ marginTop: spacing.lg }}>
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: fontSizes.xs,
          fontWeight: '700',
          textTransform: 'uppercase',
          letterSpacing: 0.4,
          marginBottom: spacing.sm,
        }}
      >
        {title}
      </Text>
      {points.length === 0 ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>{emptyMessage}</Text>
      ) : (
        <ScrollView horizontal showsHorizontalScrollIndicator={false}>
          <View
            style={[
              styles.chart,
              {
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderRadius: radius.md,
                padding: spacing.md,
                minWidth: Math.max(points.length * 76, 280),
              },
            ]}
          >
            {points.map((p) => {
              const barHeight = Math.max(12, Math.round((p.percentage / max) * 88));
              return (
                <View key={`${p.label}-${p.percentage}`} style={styles.barCol}>
                  <Text style={{ color: palette.textPrimary, fontSize: fontSizes.xs, fontWeight: '700' }}>
                    {p.percentage.toFixed(0)}%
                  </Text>
                  <View
                    style={[
                      styles.bar,
                      {
                        height: barHeight,
                        backgroundColor: `${colors.primary}88`,
                        borderRadius: radius.sm,
                      },
                    ]}
                  />
                  <Text
                    numberOfLines={2}
                    style={{
                      color: palette.textSecondary,
                      fontSize: 10,
                      textAlign: 'center',
                      marginTop: 4,
                      width: 64,
                    }}
                  >
                    {p.label}
                  </Text>
                </View>
              );
            })}
          </View>
        </ScrollView>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  chart: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    borderWidth: StyleSheet.hairlineWidth,
    minHeight: 140,
  },
  barCol: {
    alignItems: 'center',
    justifyContent: 'flex-end',
    width: 72,
    height: 120,
    marginHorizontal: 2,
  },
  bar: {
    width: 44,
    marginTop: 4,
  },
});
