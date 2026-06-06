import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface FilterTriggerButtonProps {
  activeCount: number;
  onPress: () => void;
  label?: string;
}

export const FilterTriggerButton: React.FC<FilterTriggerButtonProps> = ({
  activeCount,
  onPress,
  label = 'Filters',
}) => {
  const { palette, colors, spacing, typography, radius } = useTheme();
  const active = activeCount > 0;

  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      accessibilityLabel={`${label}${active ? `, ${activeCount} active` : ''}`}
      style={({ pressed }) => [
        styles.chip,
        {
          borderRadius: radius.full,
          borderColor: active ? colors.primary : palette.border,
          backgroundColor: active ? `${colors.primary}12` : palette.surface,
          paddingHorizontal: spacing.sm,
          paddingVertical: spacing.xs,
          opacity: pressed ? 0.85 : 1,
        },
      ]}
    >
      <Ionicons
        name="options-outline"
        size={16}
        color={active ? colors.primary : palette.textSecondary}
        style={{ marginRight: 4 }}
      />
      <Text
        style={{
          color: active ? colors.primary : palette.textSecondary,
          fontSize: typography.caption.fontSize,
          fontWeight: '600',
        }}
      >
        {active ? `${label} (${activeCount})` : label}
      </Text>
    </Pressable>
  );
};

const styles = StyleSheet.create({
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    borderWidth: StyleSheet.hairlineWidth,
  },
});
