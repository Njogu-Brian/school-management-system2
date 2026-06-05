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
  const { palette, fontSizes, spacing, radius } = useTheme();

  return (
    <View style={{ marginBottom: spacing.lg }}>
      <Text
        style={{
          marginBottom: spacing.sm,
          fontSize: fontSizes.xs,
          fontWeight: '700',
          color: palette.textSecondary,
          textTransform: 'uppercase',
          letterSpacing: 0.4,
        }}
      >
        {title}
      </Text>
      <View
        style={{
          backgroundColor: palette.surface,
          borderRadius: radius.lg,
          borderWidth: StyleSheet.hairlineWidth,
          borderColor: palette.border,
          padding: spacing.md,
        }}
      >
        {rows.map((row) => (
          <View key={row.label} style={{ marginBottom: spacing.md }}>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: 4 }}>
              {row.label}
            </Text>
            <Text style={{ color: palette.textPrimary, fontSize: fontSizes.md }}>
              {row.value || '—'}
            </Text>
          </View>
        ))}
      </View>
    </View>
  );
};
