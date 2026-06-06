import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
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
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ disabled }}
      disabled={disabled || !onPress}
      onPress={onPress}
      style={({ pressed }) => [
        styles.chip,
        elevation[1],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
          borderRadius: radius.control,
          paddingVertical: spacing.md,
          paddingHorizontal: spacing.md,
          opacity: disabled ? 0.5 : pressed ? 0.88 : 1,
        },
      ]}
    >
      <View style={[styles.iconWrap, { backgroundColor: `${colors.primary}10`, borderRadius: radius.sm }]}>
        <Ionicons name={icon} size={22} color={colors.primary} />
      </View>
      <Text
        style={[
          styles.label,
          {
            color: palette.textPrimary,
            fontSize: typography.caption.fontSize,
            marginTop: spacing.xs,
          },
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
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
    minWidth: 100,
    flex: 1,
    maxWidth: '48%',
  },
  iconWrap: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
  label: { fontWeight: '600', textAlign: 'center' },
});
