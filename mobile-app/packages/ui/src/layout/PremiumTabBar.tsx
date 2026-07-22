import { Ionicons } from '@expo/vector-icons';
import { BlurView } from 'expo-blur';
import { LinearGradient } from 'expo-linear-gradient';
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

/** Approximate clearance under floating PremiumTabBar (bar + safe area cushion). */
export const FLOATING_TAB_BAR_CLEARANCE = 96;
export const PremiumTabBar: React.FC<PremiumTabBarProps> = ({ items, activeKey, onTabPress }) => {
  const { palette, spacing, typography, radius, elevation, isDark } = useTheme();
  const insets = useSafeAreaInsets();

  const barBody = (
    <View style={[styles.barInner, { paddingVertical: spacing.sm, paddingHorizontal: 4 }]}>
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
            <View style={focused ? styles.activeLift : undefined}>
              <Soft3DIcon
                name={iconName}
                tone={focused ? tone : 'muted'}
                muted={!focused}
                active={focused}
                size={focused ? 40 : 34}
              />
            </View>
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
  );

  return (
    <View
      pointerEvents="box-none"
      style={[styles.wrap, { paddingBottom: Math.max(insets.bottom, spacing.sm) }]}
    >
      <View
        style={[
          styles.bar,
          elevation[5],
          {
            borderRadius: radius.sheet,
            marginHorizontal: spacing.md,
            borderColor: isDark ? 'rgba(255,255,255,0.12)' : 'rgba(255,255,255,0.65)',
            overflow: 'hidden',
          },
        ]}
      >
        {Platform.OS === 'ios' ? (
          <BlurView intensity={64} tint={isDark ? 'dark' : 'light'} style={StyleSheet.absoluteFill} />
        ) : (
          <BlurView
            intensity={80}
            tint={isDark ? 'dark' : 'light'}
            experimentalBlurMethod="dimezisBlurView"
            style={StyleSheet.absoluteFill}
          />
        )}
        <LinearGradient
          colors={
            isDark
              ? ['rgba(75,159,255,0.12)', 'rgba(21,26,36,0.15)']
              : ['rgba(255,255,255,0.55)', 'rgba(232,241,251,0.35)']
          }
          style={StyleSheet.absoluteFill}
        />
        {barBody}
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
    borderWidth: StyleSheet.hairlineWidth,
  },
  barInner: {
    flexDirection: 'row',
  },
  item: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 58,
  },
  activeLift: {
    shadowColor: '#004A99',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
  },
});
