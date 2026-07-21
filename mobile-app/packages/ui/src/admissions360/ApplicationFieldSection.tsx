import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface ApplicationFieldRow {
  label: string;
  value: string | null | undefined;
}

export interface ApplicationFieldSectionProps {
  title: string;
  rows: ApplicationFieldRow[];
}

export const ApplicationFieldSection: React.FC<ApplicationFieldSectionProps> = ({
  title,
  rows,
}) => {
  const { palette, typography, spacing, radius } = useTheme();

  return (
    <View style={{ marginBottom: spacing.lg }}>
      <Text
        style={{
          marginBottom: spacing.sm,
          fontSize: typography.overline.fontSize,
          fontWeight: typography.overline.fontWeight,
          color: palette.textMuted,
          textTransform: 'uppercase',
          letterSpacing: typography.overline.letterSpacing,
        }}
      >
        {title}
      </Text>
      <View
        style={{
          backgroundColor: palette.surfaceRaised,
          borderRadius: radius.card,
          borderWidth: StyleSheet.hairlineWidth,
          borderColor: palette.borderSubtle,
          padding: spacing.md,
        }}
      >
        {rows.map((row) => (
          <View key={row.label} style={{ marginBottom: spacing.md }}>
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.overline.fontSize,
                marginBottom: spacing.xs,
              }}
            >
              {row.label}
            </Text>
            <Text style={{ color: palette.textPrimary, fontSize: typography.bodyLarge.fontSize }}>
              {row.value || '—'}
            </Text>
          </View>
        ))}
      </View>
    </View>
  );
};
