import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { AccentIcon } from '../primitives/AccentIcon';
import { useTheme } from '../theme/ThemeContext';
import type { AcademicTrendCardProps } from './types';

export const AcademicTrendCard: React.FC<AcademicTrendCardProps> = ({
  examName,
  mean,
  passRate,
}) => {
  const { palette, spacing, typography, radius, elevation } = useTheme();

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
          minWidth: 140,
        },
      ]}
    >
      <AccentIcon name="trending-up" tone="teal" size={40} iconSize={18} />
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: typography.caption.fontSize,
          fontWeight: '700',
          marginTop: spacing.sm,
        }}
        numberOfLines={2}
      >
        {examName}
      </Text>
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: typography.titleSmall.fontSize,
          fontWeight: '700',
          marginTop: 4,
        }}
      >
        {mean.toFixed(1)}%
      </Text>
      {passRate != null ? (
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.caption.fontSize,
            marginTop: 2,
          }}
        >
          Pass {passRate.toFixed(0)}%
        </Text>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  card: {
    marginRight: 8,
    borderWidth: StyleSheet.hairlineWidth,
  },
});
