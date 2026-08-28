import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import type { Soft3DTone } from '../primitives/AccentIcon';
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

/** Floating capsule body height (icon + label). */
export const FLOATING_TAB_BAR_BODY_HEIGHT = 64;
/** Gap between content and the top of the capsule. Do not include safe-area here. */
export const FLOATING_TAB_BAR_CUSHION = 16;
/** Capsule + cushion only — use when the screen is already inside a bottom SafeArea. */
export const FLOATING_TAB_BAR_CONTENT_INSET =
  FLOATING_TAB_BAR_BODY_HEIGHT + FLOATING_TAB_BAR_CUSHION;
/** @deprecated Prefer useFloatingTabBarClearance(false) inside SafeArea screens. */
export const FLOATING_TAB_BAR_CLEARANCE = FLOATING_TAB_BAR_CONTENT_INSET;

/**
 * Space to keep content above the floating tab bar.
 * Pass `false` when the caller is already inside a bottom SafeArea (ScreenContainer).
 * Pass `true` (default) for absolute overlays measured from the physical screen bottom (FAB, toast).
 */
export function useFloatingTabBarClearance(includeSafeArea = true): number {
  const insets = useSafeAreaInsets();
  const chrome = FLOATING_TAB_BAR_BODY_HEIGHT + FLOATING_TAB_BAR_CUSHION;
  if (!includeSafeArea) {
    return chrome;
  }
  const safe = Math.max(insets.bottom, Platform.OS === 'android' ? 12 : 8);
  return chrome + safe;
}

/**
 * Floating brand capsule tab bar. Every tab keeps its label (idle + focused).
 */
export const PremiumTabBar: React.FC<PremiumTabBarProps> = ({ items, activeKey, onTabPress }) => {
  const { spacing, colors, isDark, zIndex } = useTheme();
  const insets = useSafeAreaInsets();
  const barBg = isDark ? colors.primaryDark : colors.primary;
  const activePill = isDark ? 'rgba(75,159,255,0.28)' : 'rgba(255,255,255,0.2)';
  const idleIcon = 'rgba(255,255,255,0.72)';
  const activeIcon = '#FFFFFF';

  return (
    <View
      pointerEvents="box-none"
      style={[
        styles.wrap,
        {
          paddingBottom: Math.max(insets.bottom, spacing.sm),
          zIndex: zIndex.nav,
        },
      ]}
    >
      <View style={[styles.capsule, { backgroundColor: barBg }]}>
        {items.map((item) => {
          const focused = item.key === activeKey;
          const iconName = (focused
            ? item.iconFocused ?? item.icon
            : item.icon) as keyof typeof Ionicons.glyphMap;

          return (
            <Pressable
              key={item.key}
              accessibilityRole="tab"
              accessibilityState={{ selected: focused }}
              accessibilityLabel={item.label}
              onPress={() => onTabPress(item.key)}
              style={styles.slot}
            >
              <View
                style={[
                  styles.item,
                  focused ? { backgroundColor: activePill } : null,
                ]}
              >
                <Ionicons name={iconName} size={20} color={focused ? activeIcon : idleIcon} />
                <Text
                  numberOfLines={1}
                  style={[styles.label, { color: focused ? activeIcon : idleIcon, fontWeight: focused ? '700' : '600' }]}
                >
                  {item.label}
                </Text>
              </View>
            </Pressable>
          );
        })}
      </View>
    </View>
  );
};

export function getPremiumTabBarOptions(_theme: {
  palette: {
    surfaceRaised: string;
    borderSubtle: string;
    primary: string;
    textMuted: string;
  };
}) {
  return {
    tabBarActiveTintColor: '#FFFFFF',
    tabBarInactiveTintColor: 'rgba(255,255,255,0.7)',
    tabBarStyle: {
      backgroundColor: 'transparent',
      borderTopWidth: 0,
      elevation: 0,
      position: 'absolute' as const,
    },
    tabBarLabelStyle: {
      fontSize: 11,
      fontWeight: '700' as const,
    },
  };
}

const styles = StyleSheet.create({
  wrap: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: 16,
    elevation: 16,
  },
  capsule: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    minHeight: 62,
    borderRadius: 999,
    paddingHorizontal: 6,
    paddingVertical: 6,
    shadowColor: '#003366',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.32,
    shadowRadius: 18,
    elevation: 12,
  },
  slot: {
    flexGrow: 1,
    flexShrink: 1,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 50,
    minWidth: 0,
  },
  item: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: 2,
    paddingHorizontal: 8,
    paddingVertical: 6,
    borderRadius: 14,
    maxWidth: '100%',
  },
  label: {
    fontSize: 10,
    letterSpacing: 0.1,
    maxWidth: '100%',
  },
});
