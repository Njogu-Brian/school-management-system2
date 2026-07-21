import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { AccentIcon, type AccentTone } from '../primitives/AccentIcon';
import { useTheme } from '../theme/ThemeContext';
import type { AcademicKpiCardProps } from './types';

const TONES: AccentTone[] = ['blue', 'teal', 'violet', 'amber', 'rose', 'emerald', 'cyan', 'indigo'];

export const AcademicKpiCard: React.FC<AcademicKpiCardProps> = ({
  label,
  value,
  icon = 'stats-chart',
}) => {
  const { palette, spacing, typography, radius, elevation } = useTheme();
  const tone = TONES[Math.abs(label.length) % TONES.length];

  return (
    <View
      style={[
        styles.card,
        elevation[2],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.card,
          padding: spacing.md,
        },
      ]}
    >
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
          numberOfLines={2}
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
        numberOfLines={1}
      >
        {value}
      </Text>
    </View>
  );
};

const styles = StyleSheet.create({
  card: {
    flex: 1,
    minWidth: 140,
    borderWidth: StyleSheet.hairlineWidth,
  },
  header: { flexDirection: 'row', alignItems: 'center' },
  label: { fontWeight: '700', flex: 1 },
  value: { fontWeight: '800' },
});
