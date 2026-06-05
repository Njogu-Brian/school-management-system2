import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import type { AcademicTrendCardProps } from './types';

export const AcademicTrendCard: React.FC<AcademicTrendCardProps> = ({
  examName,
  mean,
  passRate,
}) => {
  const { palette, spacing, fontSizes, radius } = useTheme();

  return (
    <View
      style={[
        styles.card,
        {
          backgroundColor: palette.accent,
          borderRadius: radius.md,
          padding: spacing.sm,
          minWidth: 120,
        },
      ]}
    >
      <Text style={{ color: palette.textPrimary, fontSize: fontSizes.xs, fontWeight: '700' }} numberOfLines={2}>
        {examName}
      </Text>
      <Text style={{ color: palette.textPrimary, fontSize: fontSizes.md, fontWeight: '700', marginTop: 4 }}>
        {mean.toFixed(1)}%
      </Text>
      {passRate != null ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
          Pass {passRate.toFixed(0)}%
        </Text>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  card: { marginRight: 8 },
});
