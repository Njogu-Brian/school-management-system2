import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface StaffFieldRow {
  label: string;
  value: string | null | undefined;
}

export interface StaffFieldSectionProps {
  title: string;
  rows: StaffFieldRow[];
}

export const StaffFieldSection: React.FC<StaffFieldSectionProps> = ({ title, rows }) => {
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
                marginBottom: 4,
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
