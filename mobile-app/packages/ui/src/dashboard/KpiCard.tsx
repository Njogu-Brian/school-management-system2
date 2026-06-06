import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface KpiCardProps {
  label: string;
  value: string;
  /** Optional trend caption, e.g. "+4.2% vs last week". */
  delta?: string;
  deltaPositive?: boolean;
  icon?: keyof typeof Ionicons.glyphMap;
  onPress?: () => void;
}

/** Success-state body for a KPI widget (used inside `WidgetShell`). */
export const KpiCard: React.FC<KpiCardProps> = ({
  label,
  value,
  delta,
  deltaPositive,
  icon = 'stats-chart-outline',
  onPress,
}) => {
  const { palette, colors, typography, radius, spacing } = useTheme();

  const deltaColor = deltaPositive === false ? colors.error : colors.success;

  const body = (
    <>
      <View style={styles.header}>
        <View style={[styles.iconWrap, { backgroundColor: `${colors.primary}12`, borderRadius: radius.sm }]}>
          <Ionicons name={icon} size={20} color={colors.primary} />
        </View>
        <Text
          style={[
            styles.label,
            {
              color: palette.textMuted,
              fontSize: typography.overline.fontSize,
              letterSpacing: typography.overline.letterSpacing,
            },
          ]}
        >
          {label.toUpperCase()}
        </Text>
      </View>
      <Text
        style={[
          styles.value,
          {
            color: palette.textPrimary,
            fontSize: typography.heading.fontSize,
            lineHeight: typography.heading.lineHeight,
            marginTop: spacing.xs,
          },
        ]}
      >
        {value}
      </Text>
      {delta ? (
        <Text
          style={[
            styles.delta,
            {
              color: deltaColor,
              fontSize: typography.caption.fontSize,
              marginTop: spacing.xs,
            },
          ]}
        >
          {delta}
        </Text>
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
  iconWrap: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 10,
  },
  label: { fontWeight: '600', flex: 1 },
  value: { fontWeight: '700' },
  delta: { fontWeight: '500' },
});
