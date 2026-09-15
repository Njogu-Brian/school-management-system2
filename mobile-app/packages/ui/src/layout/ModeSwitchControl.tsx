import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export type ModeSwitchKey = 'work' | 'home';

export interface ModeSwitchControlProps {
  mode: ModeSwitchKey;
  onChange: (mode: ModeSwitchKey) => void;
  workLabel?: string;
  homeLabel?: string;
  variant?: 'segmented' | 'banner';
  style?: object;
}

/**
 * Visible Work / Home (staff / parent) switch for dual-identity users.
 */
export const ModeSwitchControl: React.FC<ModeSwitchControlProps> = ({
  mode,
  onChange,
  workLabel = 'Work',
  homeLabel = 'Home',
  variant = 'segmented',
  style,
}) => {
  const { palette, colors, spacing, typography, radius } = useTheme();
  const other: ModeSwitchKey = mode === 'work' ? 'home' : 'work';
  const otherLabel = other === 'work' ? workLabel : homeLabel;

  if (variant === 'banner') {
    return (
      <View
        style={[
          {
            backgroundColor: palette.primaryMuted,
            borderColor: palette.border,
            borderWidth: 1,
            borderRadius: radius.lg,
            padding: spacing.md,
            gap: spacing.sm,
          },
          style,
        ]}
      >
        <Text style={{ color: palette.textPrimary, fontWeight: '800', fontSize: typography.body.fontSize }}>
          {mode === 'home' ? `You are in ${homeLabel} mode` : `You are in ${workLabel} mode`}
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
          {mode === 'home'
            ? `Switch to ${workLabel} to mark attendance, enter marks, and do staff tasks.`
            : `Switch to ${homeLabel} to see your children, fees, and family updates.`}
        </Text>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={`Switch to ${otherLabel}`}
          onPress={() => onChange(other)}
          style={{
            alignSelf: 'flex-start',
            flexDirection: 'row',
            alignItems: 'center',
            gap: 8,
            backgroundColor: colors.primary,
            borderRadius: radius.md,
            paddingHorizontal: spacing.md,
            paddingVertical: spacing.sm,
          }}
        >
          <Ionicons name={other === 'work' ? 'briefcase-outline' : 'home-outline'} size={16} color="#fff" />
          <Text style={{ color: '#fff', fontWeight: '800' }}>Switch to {otherLabel}</Text>
        </Pressable>
      </View>
    );
  }

  const options: Array<{ key: ModeSwitchKey; label: string; icon: keyof typeof Ionicons.glyphMap }> = [
    { key: 'work', label: workLabel, icon: 'briefcase-outline' },
    { key: 'home', label: homeLabel, icon: 'home-outline' },
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
            accessibilityRole="button"
            accessibilityState={{ selected: active }}
            accessibilityLabel={`${opt.label} mode`}
            onPress={() => onChange(opt.key)}
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
