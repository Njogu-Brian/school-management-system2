import { useAppMode } from '@erp/core';
import { useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, Text, View } from 'react-native';

/**
 * Work | Home segmented switch for dual-identity users (staff who are also parents).
 * Renders nothing for single-identity users.
 */
export const AppModeSwitch: React.FC<{ style?: object }> = ({ style }) => {
  const { mode, canSwitch, setMode } = useAppMode();
  const { palette, colors, spacing, typography, radius } = useTheme();

  if (!canSwitch) {
    return null;
  }

  const options: Array<{ key: 'work' | 'home'; label: string; icon: keyof typeof Ionicons.glyphMap }> = [
    { key: 'work', label: 'Work', icon: 'briefcase-outline' },
    { key: 'home', label: 'Home', icon: 'home-outline' },
  ];

  return (
    <View
      style={[
        {
          flexDirection: 'row',
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderWidth: 1,
          borderRadius: radius.control,
          padding: 4,
        },
        style,
      ]}
    >
      {options.map((opt) => {
        const active = mode === opt.key;
        return (
          <Pressable
            key={opt.key}
            onPress={() => void setMode(opt.key)}
            style={{
              flex: 1,
              flexDirection: 'row',
              alignItems: 'center',
              justifyContent: 'center',
              gap: 6,
              paddingVertical: spacing.sm,
              borderRadius: radius.md,
              backgroundColor: active ? colors.primary : 'transparent',
            }}
          >
            <Ionicons name={opt.icon} size={16} color={active ? '#fff' : palette.textSecondary} />
            <Text
              style={{
                color: active ? '#fff' : palette.textSecondary,
                fontWeight: '700',
                fontSize: typography.caption.fontSize,
              }}
            >
              {opt.label}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
};
