import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View, ViewStyle } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface FilterChipProps {
  label: string;
  active?: boolean;
  onPress?: () => void;
}

/** Single filter chip — pill shape with active tint. */
export const FilterChip: React.FC<FilterChipProps> = ({ label, active = false, onPress }) => {
  const { palette, colors, typography, radius, spacing } = useTheme();

  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      accessibilityState={{ selected: active }}
      style={[
        styles.chip,
        {
          borderRadius: radius.chip,
          borderColor: active ? colors.primary : palette.border,
          backgroundColor: active ? `${colors.primary}14` : palette.surface,
          paddingHorizontal: spacing.md,
          paddingVertical: spacing.xs + 2,
        },
      ]}
    >
      <Text
        style={{
          color: active ? colors.primary : palette.textSecondary,
          fontSize: typography.caption.fontSize,
          fontWeight: active ? '700' : '500',
        }}
      >
        {label}
      </Text>
    </Pressable>
  );
};

export interface FilterChipRowProps {
  label?: string;
  children: React.ReactNode;
  style?: ViewStyle;
}

/** Horizontal scroll row of filter chips with optional section label. */
export const FilterChipRow: React.FC<FilterChipRowProps> = ({ label, children, style }) => {
  const { palette, typography, spacing } = useTheme();

  return (
    <View style={[{ marginBottom: spacing.sm }, style]}>
      {label ? (
        <Text
          style={[
            styles.sectionLabel,
            {
              color: palette.textMuted,
              fontSize: typography.overline.fontSize,
              letterSpacing: typography.overline.letterSpacing,
              marginBottom: spacing.xs,
            },
          ]}
        >
          {label.toUpperCase()}
        </Text>
      ) : null}
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={[styles.row, { gap: spacing.xs, paddingVertical: spacing.xs }]}
      >
        {children}
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  chip: { borderWidth: 1 },
  sectionLabel: { fontWeight: '600', marginLeft: 2 },
  row: { flexDirection: 'row', alignItems: 'center' },
});
