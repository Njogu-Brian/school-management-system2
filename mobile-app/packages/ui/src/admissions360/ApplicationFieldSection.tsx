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
