import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Soft3DIcon, type Soft3DTone } from '../primitives/AccentIcon';
import { useTheme } from '../theme/ThemeContext';

export interface PremiumTabItem {
  key: string;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  iconFocused?: keyof typeof Ionicons.glyphMap;
  tone?: Soft3DTone;
}

export interface PremiumTabBarProps {
  items: PremiumTabItem[];
  activeKey: string;
  onTabPress: (key: string) => void;
}

const DEFAULT_TONES: Soft3DTone[] = ['blue', 'indigo', 'emerald', 'cyan'];

/** Docked tab bar body height (icons + label). */
export const FLOATING_TAB_BAR_BODY_HEIGHT = 58;
export const FLOATING_TAB_BAR_CUSHION = 0;
export const FLOATING_TAB_BAR_CLEARANCE = FLOATING_TAB_BAR_BODY_HEIGHT + 16;

/** Extra scroll padding so content clears the docked tab bar + home indicator. */
export function useFloatingTabBarClearance(_includeSafeArea = true): number {
  const insets = useSafeAreaInsets();
  const safe = Math.max(insets.bottom, Platform.OS === 'android' ? 8 : 0);
  return FLOATING_TAB_BAR_BODY_HEIGHT + safe + 8;
}

/**
 * Solid docked bottom tab bar (always visible). No floating/liquid pill animation.
 */
export const PremiumTabBar: React.FC<PremiumTabBarProps> = ({ items, activeKey, onTabPress }) => {
  const { palette, spacing, typography, isDark, colors } = useTheme();
  const insets = useSafeAreaInsets();
  const compact = items.length >= 6;
  const focusedSize = compact ? 26 : 28;
  const idleSize = compact ? 24 : 26;
  const labelSize = compact ? Math.max(9, typography.tiny.fontSize - 1) : typography.tiny.fontSize;

  return (
    <View
      style={[
        styles.wrap,
        {
          paddingBottom: Math.max(insets.bottom, Platform.OS === 'android' ? 6 : 4),
          backgroundColor: isDark ? palette.surfaceRaised : palette.surface,
          borderTopColor: isDark ? 'rgba(255,255,255,0.1)' : palette.borderSubtle,
        },
      ]}
    >
      <View style={[styles.barInner, { paddingVertical: spacing.xs, paddingHorizontal: compact ? 2 : 4 }]}>
        {items.map((item, index) => {
          const focused = item.key === activeKey;
          const iconName = (focused
            ? item.iconFocused ?? item.icon
            : item.icon) as keyof typeof Ionicons.glyphMap;
          const tone = item.tone ?? DEFAULT_TONES[index % DEFAULT_TONES.length];
          return (
            <Pressable
              key={item.key}
              accessibilityRole="tab"
              accessibilityState={{ selected: focused }}
              accessibilityLabel={item.label}
              onPress={() => onTabPress(item.key)}
              style={styles.item}
            >
              <Soft3DIcon
                name={iconName}
                tone={focused ? tone : 'muted'}
                muted={!focused}
                active={focused}
                size={focused ? focusedSize : idleSize}
              />
              <Text
                style={{
                  marginTop: 2,
                  color: focused ? colors.primary : palette.textMuted,
                  fontSize: labelSize,
                  fontWeight: focused ? '700' : '500',
                }}
                numberOfLines={1}
                adjustsFontSizeToFit
                minimumFontScale={0.75}
              >
                {item.label}
              </Text>
            </Pressable>
          );
        })}
      </View>
    </View>
  );
};

export function getPremiumTabBarOptions(theme: {
  palette: {
    surfaceRaised: string;
    borderSubtle: string;
    primary: string;
    textMuted: string;
  };
}) {
  return {
    tabBarActiveTintColor: theme.palette.primary,
    tabBarInactiveTintColor: theme.palette.textMuted,
    tabBarStyle: {
      backgroundColor: theme.palette.surfaceRaised,
      borderTopWidth: StyleSheet.hairlineWidth,
      borderTopColor: theme.palette.borderSubtle,
      elevation: 8,
    },
    tabBarLabelStyle: {
      fontSize: 11,
      fontWeight: '700' as const,
    },
  };
}

const styles = StyleSheet.create({
  wrap: {
    borderTopWidth: StyleSheet.hairlineWidth,
  },
  barInner: {
    flexDirection: 'row',
  },
  item: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 52,
  },
});
