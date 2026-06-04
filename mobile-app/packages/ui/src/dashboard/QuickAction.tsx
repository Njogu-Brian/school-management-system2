import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface QuickActionProps {
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  onPress?: () => void;
  disabled?: boolean;
}

export const QuickAction: React.FC<QuickActionProps> = ({
  label,
  icon,
  onPress,
  disabled = false,
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ disabled }}
      disabled={disabled || !onPress}
      onPress={onPress}
      style={({ pressed }) => [
        styles.chip,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.md,
          paddingVertical: spacing.sm,
          paddingHorizontal: spacing.md,
          opacity: disabled ? 0.5 : pressed ? 0.85 : 1,
        },
      ]}
    >
      <Ionicons name={icon} size={20} color={colors.primary} />
      <Text
        style={[
          styles.label,
          { color: palette.textPrimary, fontSize: fontSizes.xs, marginLeft: spacing.xs },
        ]}
        numberOfLines={2}
      >
        {label}
      </Text>
    </Pressable>
  );
};

const styles = StyleSheet.create({
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
    minWidth: 140,
    flex: 1,
    maxWidth: '48%',
  },
  label: { fontWeight: '600', flexShrink: 1 },
});
