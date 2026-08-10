import React, { useMemo } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../../theme/ThemeContext';
import type { PerformanceTrendPointData } from './types';

export interface PerformanceTrendProps {
  title?: string;
  points: PerformanceTrendPointData[];
  emptyMessage?: string;
}

const BAR_MAX_H = 96;
const COL_W = 52;
const LABEL_SLOT_H = 56;

export const PerformanceTrend: React.FC<PerformanceTrendProps> = ({
  title = 'Performance trend',
  points,
  emptyMessage = 'Not enough data for a trend yet.',
}) => {
  const { palette, colors, spacing, typography, radius } = useTheme();
  const chartWidth = useMemo(() => Math.max(points.length * COL_W + 8, 200), [points.length]);

  return (
    <View style={{ marginTop: spacing.lg }}>
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: typography.caption.fontSize,
          fontWeight: '700',
          textTransform: 'uppercase',
          letterSpacing: 0.4,
          marginBottom: spacing.sm,
        }}
      >
        {title}
      </Text>
      {points.length === 0 ? (
        <Text style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>{emptyMessage}</Text>
      ) : (
        <ScrollView horizontal showsHorizontalScrollIndicator={points.length > 5}>
          <View
            style={[
              styles.chart,
              {
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderRadius: radius.md,
                paddingTop: spacing.sm,
                paddingHorizontal: spacing.xs,
                width: chartWidth,
                minHeight: BAR_MAX_H + LABEL_SLOT_H + 28,
              },
            ]}
          >
            {points.map((p, idx) => {
              const h = Math.max(6, Math.round((Math.min(p.percentage, 100) / 100) * BAR_MAX_H));
              const isLatest = idx === points.length - 1;
              return (
                <View key={`bar-${idx}-${p.label}-${p.percentage}`} style={styles.col}>
                  <View style={styles.barStack}>
                    <Text
                      style={{
                        color: isLatest ? palette.textPrimary : palette.textSecondary,
                        fontSize: 10,
                        fontWeight: isLatest ? '800' : '600',
                        marginBottom: 4,
                      }}
                    >
                      {p.percentage.toFixed(0)}%
                    </Text>
                    <View
                      style={[
                        styles.barTrack,
                        {
                          height: BAR_MAX_H,
                          backgroundColor: palette.surfaceMuted,
                          borderRadius: radius.sm,
                        },
                      ]}
                    >
                      <View
                        style={[
                          styles.barFill,
                          {
                            height: h,
                            backgroundColor: colors.primary,
                            borderTopLeftRadius: radius.sm,
                            borderTopRightRadius: radius.sm,
                            opacity: isLatest ? 1 : 0.82,
                          },
                        ]}
                      />
                    </View>
                  </View>
                  <View style={styles.labelSlot}>
                    <Text
                      numberOfLines={2}
                      style={[
                        styles.angledLabel,
                        {
                          color: isLatest ? palette.textPrimary : palette.textMuted,
                          fontWeight: isLatest ? '700' : '500',
                        },
                      ]}
                    >
                      {p.label}
                    </Text>
                  </View>
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
    borderWidth: StyleSheet.hairlineWidth,
  },
  col: {
    width: COL_W,
    alignItems: 'center',
  },
  barStack: {
    height: BAR_MAX_H + 20,
    alignItems: 'center',
    justifyContent: 'flex-end',
  },
  barTrack: {
    width: 28,
    justifyContent: 'flex-end',
    overflow: 'hidden',
  },
  barFill: {
    width: '100%',
  },
  labelSlot: {
    height: LABEL_SLOT_H,
    width: COL_W,
    alignItems: 'center',
    justifyContent: 'flex-start',
    marginTop: 6,
  },
  angledLabel: {
    width: 78,
    fontSize: 9,
    lineHeight: 11,
    textAlign: 'left',
    transform: [{ rotate: '-42deg' }, { translateY: 10 }, { translateX: -6 }],
  },
});
