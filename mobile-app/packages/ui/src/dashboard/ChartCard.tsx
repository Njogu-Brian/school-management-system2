import React from 'react';
import { StyleSheet, Text, View, ViewStyle } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface ChartCardProps {
  title: string;
  subtitle?: string;
  children: React.ReactNode;
  style?: ViewStyle;
}

/** Elevated card wrapper for charts — consistent V2 chrome. */
export const ChartCard: React.FC<ChartCardProps> = ({ title, subtitle, children, style }) => {
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
        },
        style,
      ]}
    >
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: typography.title.fontSize,
          fontWeight: typography.title.fontWeight,
          marginBottom: subtitle ? spacing.xs : spacing.sm,
        }}
      >
        {title}
      </Text>
      {subtitle ? (
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.caption.fontSize,
            marginBottom: spacing.sm,
          }}
        >
          {subtitle}
        </Text>
      ) : null}
      {children}
    </View>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth, overflow: 'hidden' },
});
