import {
  clearSelectedChildId,
  invalidateQueriesForAppMode,
  useAppMode,
  useCurrentUser,
  type AppMode,
} from '@erp/core';
import { useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import { useQueryClient } from '@tanstack/react-query';
import React, { useCallback } from 'react';
import { Pressable, Text, View } from 'react-native';

/**
 * Home | Work switch for Admin app dual-identity users.
 * Mirrors Users app cache boundary behaviour.
 */
export const AppModeSwitch: React.FC<{ style?: object }> = ({ style }) => {
  const { mode, canSwitch, setMode } = useAppMode();
  const user = useCurrentUser();
  const queryClient = useQueryClient();
  const { palette, colors, spacing, typography, radius } = useTheme();

  const switchMode = useCallback(
    async (next: AppMode) => {
      if (next === mode) return;
      invalidateQueriesForAppMode(queryClient, next);
      if (next === 'work' && user?.id) {
        await clearSelectedChildId(user.id);
      }
      await setMode(next);
    },
    [mode, queryClient, setMode, user?.id],
  );

  if (!canSwitch) {
    return null;
  }

  const options: Array<{ key: AppMode; label: string; icon: keyof typeof Ionicons.glyphMap }> = [
    { key: 'home', label: 'Home', icon: 'home-outline' },
    { key: 'work', label: 'Work', icon: 'briefcase-outline' },
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
            onPress={() => void switchMode(opt.key)}
            style={{
              flex: 1,
              flexDirection: 'row',
              alignItems: 'center',
              justifyContent: 'center',
              gap: 6,
              paddingVertical: spacing.sm,
              borderRadius: radius.md,
              backgroundColor: active ? colors.primary : 'transparent',
              minHeight: 44,
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
