import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { AccentIcon, type AccentTone } from '../primitives/AccentIcon';

const TONES: AccentTone[] = ['blue', 'teal', 'violet', 'amber', 'rose', 'emerald', 'cyan', 'indigo'];

export interface KpiCardProps {
  label: string;
  value: string;
  delta?: string;
  deltaPositive?: boolean;
  icon?: keyof typeof Ionicons.glyphMap;
  accentTone?: AccentTone;
  onPress?: () => void;
}

/** Premium KPI body — gradient accent icon + large value. */
export const KpiCard: React.FC<KpiCardProps> = ({
  label,
  value,
  delta,
  deltaPositive,
  icon = 'stats-chart',
  accentTone,
  onPress,
}) => {
  const { palette, colors, typography, spacing } = useTheme();
  const tone = accentTone ?? TONES[Math.abs(label.length) % TONES.length];
  const deltaColor = deltaPositive === false ? colors.error : colors.success;

  const body = (
    <>
      <View style={styles.header}>
        <AccentIcon name={icon} tone={tone} size={44} iconSize={20} />
        <Text
          style={[
            styles.label,
            {
              color: palette.textMuted,
              fontSize: typography.overline.fontSize,
              letterSpacing: typography.overline.letterSpacing,
              marginLeft: spacing.sm,
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
});
