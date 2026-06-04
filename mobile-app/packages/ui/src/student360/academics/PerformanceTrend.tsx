import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
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
        <View
          style={[
            styles.chart,
            {
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderRadius: radius.md,
              padding: spacing.md,
            },
          ]}
        >
          {points.map((p) => {
            const heightPct = Math.max(8, Math.round((p.percentage / max) * 100));
            return (
              <View key={`${p.label}-${p.percentage}`} style={styles.barCol}>
                <Text style={{ color: palette.textPrimary, fontSize: fontSizes.xs, fontWeight: '700' }}>
                  {p.percentage.toFixed(0)}%
                </Text>
                <View
                  style={[
                    styles.bar,
                    {
                      height: `${heightPct}%`,
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
                    minHeight: 28,
                  }}
                >
                  {p.label}
                </Text>
              </View>
            );
          })}
        </View>
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
    flex: 1,
    alignItems: 'center',
    justifyContent: 'flex-end',
    maxWidth: 72,
    height: 120,
  },
  bar: {
    width: '70%',
    minHeight: 8,
    marginTop: 4,
  },
});
