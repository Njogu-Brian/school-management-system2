import { Ionicons } from '@expo/vector-icons';
import React, { useMemo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import Svg, { Circle, Polyline } from 'react-native-svg';
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

function Sparkline({
  points,
  color,
  width,
  height,
}: {
  points: number[];
  color: string;
  width: number;
  height: number;
}) {
  const coords = useMemo(() => {
    if (points.length === 0) return '';
    const min = Math.min(...points, 0);
    const max = Math.max(...points, 100);
    const span = Math.max(max - min, 1);
    const step = points.length > 1 ? width / (points.length - 1) : width;
    return points
      .map((v, i) => {
        const x = i * step;
        const y = height - ((v - min) / span) * (height - 8) - 4;
        return `${x},${y}`;
      })
      .join(' ');
  }, [points, width, height]);

  const last = useMemo(() => {
    if (points.length === 0) return null;
    const parts = coords.split(' ');
    const lastPair = parts[parts.length - 1]?.split(',') ?? [];
    return { x: Number(lastPair[0]), y: Number(lastPair[1]) };
  }, [coords, points.length]);

  if (points.length < 2) {
    return (
      <View style={{ height, justifyContent: 'center' }}>
        <View style={{ height: 3, borderRadius: 2, backgroundColor: `${color}55` }} />
      </View>
    );
  }

  return (
    <Svg width={width} height={height}>
      <Polyline points={coords} fill="none" stroke={color} strokeWidth={2.5} strokeLinejoin="round" strokeLinecap="round" />
      {last ? <Circle cx={last.x} cy={last.y} r={4} fill={color} /> : null}
    </Svg>
  );
}

/** Modern overall / subject progress card with sparkline and up/down/stale badge. */
export const ProgressTrendPanel: React.FC<ProgressTrendPanelProps> = ({
  title,
  subtitle,
  points,
  direction = 'stale',
  delta = null,
  emptyMessage = 'Not enough scores yet to chart progress.',
}) => {
  const { palette, spacing, typography, radius } = useTheme();
  const meta = toneMeta(direction);
  const latest = points.length > 0 ? points[points.length - 1].percentage : null;
  const values = points.map((p) => p.percentage);

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
            <Text style={{ color: palette.textPrimary, fontSize: 28, fontWeight: '800' }}>
              {latest != null ? `${latest.toFixed(0)}%` : '—'}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginLeft: spacing.sm }}>
              {delta == null
                ? 'Latest score'
                : delta > 0
                  ? `+${delta.toFixed(1)} pts vs prior`
                  : delta < 0
                    ? `${delta.toFixed(1)} pts vs prior`
                    : 'No change vs prior'}
            </Text>
          </View>
          <View style={{ marginTop: spacing.md }}>
            <Sparkline points={values} color={meta.color} width={280} height={56} />
          </View>
          <View style={[styles.labels, { marginTop: spacing.xs }]}>
            {points.slice(-4).map((p) => (
              <Text
                key={`${p.label}-${p.percentage}`}
                numberOfLines={1}
                style={{ flex: 1, color: palette.textMuted, fontSize: 10, textAlign: 'center' }}
              >
                {p.label}
              </Text>
            ))}
          </View>
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
  labels: { flexDirection: 'row', gap: 4 },
});
