import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '../theme/ThemeContext';

export interface PremiumTabItem {
  key: string;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  iconFocused?: keyof typeof Ionicons.glyphMap;
}

export interface PremiumTabBarProps {
  items: PremiumTabItem[];
  activeKey: string;
  onTabPress: (key: string) => void;
}

/**
 * Floating premium bottom bar — not stock Material tabs.
 */
export const PremiumTabBar: React.FC<PremiumTabBarProps> = ({ items, activeKey, onTabPress }) => {
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();
  const insets = useSafeAreaInsets();

  return (
    <View
      pointerEvents="box-none"
      style={[styles.wrap, { paddingBottom: Math.max(insets.bottom, spacing.sm) }]}
    >
      <View
        style={[
          styles.bar,
          elevation[4],
          {
            backgroundColor: palette.surfaceRaised,
            borderColor: palette.borderSubtle,
            borderRadius: radius.sheet,
            marginHorizontal: spacing.md,
            paddingVertical: spacing.sm,
          },
        ]}
      >
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
              style={styles.item}
            >
              {focused ? (
                <LinearGradient
                  colors={[palette.primary, colors.primaryLight]}
                  style={[styles.activePill, { borderRadius: radius.full }]}
                >
                  <Ionicons name={iconName} size={22} color={palette.textOnPrimary} />
                </LinearGradient>
              ) : (
                <View style={styles.inactiveIcon}>
                  <Ionicons name={iconName} size={22} color={palette.textMuted} />
                </View>
              )}
              <Text
                style={{
                  marginTop: 4,
                  color: focused ? palette.primary : palette.textMuted,
                  fontSize: typography.tiny.fontSize,
                  fontWeight: focused ? '700' : '500',
                }}
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

/** Style helpers when using default RN tab bar as fallback. */
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
  },
  bar: {
    flexDirection: 'row',
    borderWidth: StyleSheet.hairlineWidth,
    paddingHorizontal: 6,
  },
  item: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 52,
  },
  activePill: {
    width: 48,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
  },
  inactiveIcon: {
    width: 48,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
