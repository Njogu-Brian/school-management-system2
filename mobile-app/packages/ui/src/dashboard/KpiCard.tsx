import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { AccentIcon, type AccentTone } from '../primitives/AccentIcon';

export interface KpiStatChip {
  label: string;
  value: string;
}

export interface KpiCardProps {
  label: string;
  value: string;
  delta?: string;
  deltaPositive?: boolean;
  icon?: keyof typeof Ionicons.glyphMap;
  /** Secondary breakdown chips (e.g. present / absent / unmarked). */
  stats?: KpiStatChip[];
  /** @deprecated Glyphs own their colors. */
  accentTone?: AccentTone;
  onPress?: () => void;
}

/** Premium KPI body — free-standing soft-3D icon + large value. */
export const KpiCard: React.FC<KpiCardProps> = ({
  label,
  value,
  delta,
  deltaPositive,
  icon = 'stats-chart',
  stats,
  onPress,
}) => {
  const { palette, colors, typography, spacing, radius } = useTheme();
  const deltaColor = deltaPositive === false ? colors.error : colors.success;

  const body = (
    <>
      <View style={styles.header}>
        <AccentIcon name={icon} size={48} />
        <Text
          style={[
            styles.label,
            {
              color: palette.textMain,
              fontSize: typography.titleSmall.fontSize,
              lineHeight: typography.titleSmall.lineHeight,
              marginLeft: spacing.sm,
            },
          ]}
        >
          {label}
        </Text>
      </View>
      <Text
        style={[
          styles.value,
          {
            color: palette.textMain,
            fontSize: typography.headlineLarge.fontSize,
            lineHeight: typography.headlineLarge.lineHeight,
            marginTop: spacing.sm,
          },
        ]}
      >
        {value}
      </Text>
      {delta ? (
        <Text
          style={{
            color: deltaColor,
            fontSize: typography.caption.fontSize,
            marginTop: spacing.xs,
            fontWeight: '600',
          }}
        >
          {delta}
        </Text>
      ) : null}
      {stats && stats.length > 0 ? (
        <View style={[styles.statsRow, { gap: spacing.xs, marginTop: spacing.sm }]}>
          {stats.map((chip) => (
            <View
              key={chip.label}
              style={[
                styles.statChip,
                {
                  backgroundColor: palette.surfaceMuted,
                  borderRadius: radius.sm,
                  paddingHorizontal: spacing.sm,
                  paddingVertical: spacing.xs,
                },
              ]}
            >
              <Text
                style={{
                  color: palette.textMuted,
                  fontSize: typography.caption.fontSize,
                  fontWeight: '600',
                }}
              >
                {chip.label}
              </Text>
              <Text
                style={{
                  color: palette.textMain,
                  fontSize: typography.body.fontSize,
                  fontWeight: '800',
                  marginTop: 2,
                }}
              >
                {chip.value}
              </Text>
            </View>
          ))}
        </View>
      ) : null}
    </>
  );

  if (onPress) {
    return (
      <Pressable onPress={onPress} accessibilityRole="button">
        {body}
      </Pressable>
    );
  }

  return body;
};

const styles = StyleSheet.create({
  header: { flexDirection: 'row', alignItems: 'center' },
  label: { fontWeight: '700', flex: 1 },
  value: { fontWeight: '800' },
  statsRow: { flexDirection: 'row', flexWrap: 'wrap' },
  statChip: { flexGrow: 1, minWidth: '28%' },
});