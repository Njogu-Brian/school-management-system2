import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../../theme/ThemeContext';
import type { AcademicOverviewCardProps } from './types';

export const AcademicOverviewCard: React.FC<AcademicOverviewCardProps> = ({
  average,
  grade,
  position,
  assessmentCount,
  trendLabel,
  trendDelta,
  isLoading,
}) => {
  const { palette, colors, spacing, typography, radius, shadows } = useTheme();

  if (isLoading) {
    return (
      <View style={[styles.card, { backgroundColor: palette.surface, borderRadius: radius.md, padding: spacing.lg }]}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  const trendUp = trendDelta != null && trendDelta > 0;
  const trendDown = trendDelta != null && trendDelta < 0;
  const trendColor = trendUp ? colors.success : trendDown ? colors.error : palette.textSecondary;

  return (
    <View
      style={[
        styles.card,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.md,
          padding: spacing.md,
        },
        shadows.sm,
      ]}
    >
      <Text style={[styles.heading, { color: palette.textSecondary, fontSize: typography.caption.fontSize }]}>
        Academic overview
      </Text>
      <View style={[styles.grid, { marginTop: spacing.sm, gap: spacing.sm }]}>
        <Metric label="Average" value={average} />
        <Metric label="Grade" value={grade} />
        <Metric label="Position" value={position} />
        <Metric label="Assessments" value={assessmentCount} />
      </View>
      <View style={[styles.trendRow, { marginTop: spacing.md, borderTopColor: palette.border }]}>
        <Ionicons name="analytics-outline" size={16} color={colors.primary} />
        <Text style={{ color: palette.textPrimary, fontSize: typography.body.fontSize, marginLeft: 6, flex: 1 }}>
          {trendLabel}
        </Text>
        {trendDelta != null ? (
          <Text style={{ color: trendColor, fontSize: typography.body.fontSize, fontWeight: '700' }}>
            {trendDelta > 0 ? '+' : ''}
            {trendDelta}%
          </Text>
        ) : (
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>—</Text>
        )}
      </View>
    </View>
  );
};

const Metric: React.FC<{ label: string; value: string }> = ({ label, value }) => {
  const { palette, typography } = useTheme();
  return (
    <View style={styles.metric}>
      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, fontWeight: '600' }}>{label}</Text>
      <Text style={{ color: palette.textPrimary, fontSize: typography.title.fontSize, fontWeight: '700', marginTop: 2 }}>
        {value}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  heading: { fontWeight: '700', letterSpacing: 0.4, textTransform: 'uppercase' },
  grid: { flexDirection: 'row', flexWrap: 'wrap' },
  metric: { width: '48%', minWidth: 120 },
  trendRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingTop: 12,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
});
