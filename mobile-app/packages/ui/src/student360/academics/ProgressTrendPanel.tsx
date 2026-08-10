import { Ionicons } from '@expo/vector-icons';
import React, { useMemo } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../../theme/ThemeContext';

export type ProgressTone = 'up' | 'down' | 'stale';

export interface ProgressPoint {
  label: string;
  percentage: number;
}

export interface ProgressTrendPanelProps {
  title: string;
  subtitle?: string;
  points: ProgressPoint[];
  direction?: ProgressTone;
  delta?: number | null;
  emptyMessage?: string;
}

const BAR_MAX_H = 100;
const COL_W = 52;
const LABEL_SLOT_H = 58;

function toneMeta(direction: ProgressTone): {
  label: string;
  color: string;
  icon: keyof typeof Ionicons.glyphMap;
} {
  if (direction === 'up') {
    return { label: 'Improving', color: '#059669', icon: 'trending-up' };
  }
  if (direction === 'down') {
    return { label: 'Declining', color: '#DC2626', icon: 'trending-down' };
  }
  return { label: 'Stable', color: '#64748B', icon: 'remove-outline' };
}

function barFill(percent: number, tone: string, primary: string): string {
  if (percent >= 70) return tone === '#059669' ? tone : primary;
  if (percent >= 50) return primary;
  if (percent >= 40) return '#D97706';
  return '#DC2626';
}

/** Overall / subject progress as a bar chart with angled exam labels. */
export const ProgressTrendPanel: React.FC<ProgressTrendPanelProps> = ({
  title,
  subtitle,
  points,
  direction = 'stale',
  delta = null,
  emptyMessage = 'Not enough scores yet to chart progress.',
}) => {
  const { palette, spacing, typography, radius, colors } = useTheme();
  const meta = toneMeta(direction);
  const latest = points.length > 0 ? points[points.length - 1].percentage : null;

  const chartWidth = useMemo(
    () => Math.max(points.length * COL_W + 8, 200),
    [points.length],
  );

  return (
    <View
      style={[
        styles.card,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.lg,
          padding: spacing.md,
          marginBottom: spacing.sm,
        },
      ]}
    >
      <View style={styles.headerRow}>
        <View style={{ flex: 1, paddingRight: spacing.sm }}>
          <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.body.fontSize }}>
            {title}
          </Text>
          {subtitle ? (
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
              {subtitle}
            </Text>
          ) : null}
        </View>
        <View style={[styles.badge, { backgroundColor: `${meta.color}18` }]}>
          <Ionicons name={meta.icon} size={14} color={meta.color} />
          <Text style={{ color: meta.color, fontWeight: '700', fontSize: 11 }}>{meta.label}</Text>
        </View>
      </View>

      {points.length === 0 ? (
        <Text style={{ color: palette.textMuted, marginTop: spacing.sm, fontSize: typography.caption.fontSize }}>
          {emptyMessage}
        </Text>
      ) : (
        <>
          <View style={[styles.metrics, { marginTop: spacing.sm }]}>
            <Text style={{ color: palette.textPrimary, fontSize: 26, fontWeight: '800' }}>
              {latest != null ? `${latest.toFixed(0)}%` : '—'}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginLeft: spacing.sm, flex: 1 }}>
              {delta == null
                ? `${points.length} exam${points.length === 1 ? '' : 's'} · latest`
                : delta > 0
                  ? `+${delta.toFixed(1)} pts vs prior · ${points.length} exams`
                  : delta < 0
                    ? `${delta.toFixed(1)} pts vs prior · ${points.length} exams`
                    : `No change vs prior · ${points.length} exams`}
            </Text>
          </View>

          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={points.length > 5}
            style={{ marginTop: spacing.md }}
            contentContainerStyle={{ paddingRight: spacing.sm }}
          >
            <View style={[styles.chart, { width: chartWidth }]}>
              {/* Y-axis guide line at baseline */}
              <View
                style={[
                  styles.baseline,
                  {
                    backgroundColor: palette.borderSubtle,
                    bottom: LABEL_SLOT_H,
                  },
                ]}
              />
              {points.map((p, index) => {
                const h = Math.max(6, Math.round((Math.min(p.percentage, 100) / 100) * BAR_MAX_H));
                const fill = barFill(p.percentage, meta.color, colors.primary);
                const isLatest = index === points.length - 1;
                return (
                  <View key={`bar-${index}-${p.label}-${p.percentage}`} style={styles.col}>
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
                              backgroundColor: fill,
                              borderTopLeftRadius: radius.sm,
                              borderTopRightRadius: radius.sm,
                              opacity: isLatest ? 1 : 0.85,
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
        </>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  headerRow: { flexDirection: 'row', alignItems: 'flex-start' },
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 999,
  },
  metrics: { flexDirection: 'row', alignItems: 'baseline' },
  chart: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    minHeight: BAR_MAX_H + LABEL_SLOT_H + 28,
    paddingTop: 4,
  },
  baseline: {
    position: 'absolute',
    left: 0,
    right: 0,
    height: StyleSheet.hairlineWidth,
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
    overflow: 'visible',
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
