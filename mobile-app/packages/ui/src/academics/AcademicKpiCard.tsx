import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { AcademicKpiCardProps } from './types';

export const AcademicKpiCard: React.FC<AcademicKpiCardProps> = ({ label, value, icon }) => {
  const { palette, colors, spacing, fontSizes, radius, shadows } = useTheme();

  return (
    <View
      style={[
        styles.card,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.lg,
          padding: spacing.md,
        },
        shadows.sm,
      ]}
    >
      {icon ? (
        <Ionicons name={icon} size={20} color={colors.primary} style={{ marginBottom: spacing.xs }} />
      ) : null}
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '600' }}>
        {label}
      </Text>
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: fontSizes.lg,
          fontWeight: '700',
          marginTop: 4,
        }}
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
});
