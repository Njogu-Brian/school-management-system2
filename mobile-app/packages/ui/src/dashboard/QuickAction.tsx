import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { AccentIcon, type AccentTone } from '../primitives/AccentIcon';

export interface QuickActionProps {
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  onPress?: () => void;
  disabled?: boolean;
  /** @deprecated Glyphs own their colors; kept for API compat. */
  accentTone?: AccentTone;
}

export const QuickAction: React.FC<QuickActionProps> = ({
  label,
  icon,
  onPress,
  disabled = false,
}) => {
  const { palette, spacing, typography, radius, elevation } = useTheme();

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ disabled }}
      disabled={disabled || !onPress}
      onPress={onPress}
      style={({ pressed }) => [
        styles.chip,
        elevation[2],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.card,
          paddingVertical: spacing.md,
          paddingHorizontal: spacing.sm,
          opacity: disabled ? 0.5 : pressed ? 0.9 : 1,
        },
      ]}
    >
      <AccentIcon name={icon} size={56} />
      <Text
        style={{
          color: palette.textMain,
          fontSize: typography.caption.fontSize,
          marginTop: spacing.sm,
          fontWeight: '700',
          textAlign: 'center',
        }}
        numberOfLines={2}
      >
        {label}
      </Text>
    </Pressable>
  );
};

const styles = StyleSheet.create({
  chip: {
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
    minWidth: 100,
    flex: 1,
    maxWidth: '48%',
  },
});
