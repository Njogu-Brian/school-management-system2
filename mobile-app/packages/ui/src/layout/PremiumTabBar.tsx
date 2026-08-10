import { Ionicons } from '@expo/vector-icons';
import { BlurView } from 'expo-blur';
import { LinearGradient } from 'expo-linear-gradient';
import React, { useEffect } from 'react';
import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withSpring,
} from 'react-native-reanimated';
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

export const FLOATING_TAB_BAR_BODY_HEIGHT = 96;
export const FLOATING_TAB_BAR_CUSHION = 32;
export const FLOATING_TAB_BAR_CLEARANCE =
  FLOATING_TAB_BAR_BODY_HEIGHT + FLOATING_TAB_BAR_CUSHION + 48;

export function useFloatingTabBarClearance(_includeSafeArea = true): number {
  const insets = useSafeAreaInsets();
  const safe = Math.max(insets.bottom, Platform.OS === 'android' ? 16 : 8);
  return FLOATING_TAB_BAR_BODY_HEIGHT + FLOATING_TAB_BAR_CUSHION + safe;
}

/**
 * Floating liquid-style tab bar — active tab lifts with a soft pill indicator
 * that slides under the focused icon (no detached circle overlay).
 */
export const PremiumTabBar: React.FC<PremiumTabBarProps> = ({ items, activeKey, onTabPress }) => {
  const { palette, spacing, typography, radius, elevation, isDark, colors } = useTheme();
  const insets = useSafeAreaInsets();
  const compact = items.length >= 6;
  const focusedSize = compact ? 34 : 40;
  const idleSize = compact ? 28 : 34;
  const labelSize = compact ? Math.max(9, typography.tiny.fontSize - 1) : typography.tiny.fontSize;
  const activeIndex = Math.max(
    0,
    items.findIndex((i) => i.key === activeKey),
  );
  const indicatorX = useSharedValue(0);
  const [barWidth, setBarWidth] = React.useState(0);
  const pillWidth = compact ? 44 : 52;

  useEffect(() => {
    if (barWidth <= 0 || items.length === 0) return;
    const slot = barWidth / items.length;
    indicatorX.value = withSpring(slot * activeIndex + slot / 2 - pillWidth / 2, {
      damping: 20,
      stiffness: 220,
    });
  }, [activeIndex, barWidth, items.length, indicatorX, pillWidth]);

  const indicatorStyle = useAnimatedStyle(() => ({
    transform: [{ translateX: indicatorX.value }],
  }));

  const barBody = (
    <View
      style={[styles.barInner, { paddingVertical: spacing.sm, paddingHorizontal: compact ? 2 : 4 }]}
      onLayout={(e) => setBarWidth(e.nativeEvent.layout.width)}
    >
      {barWidth > 0 && activeKey ? (
        <Animated.View
          pointerEvents="none"
          style={[
            styles.liquidPill,
            indicatorStyle,
            {
              width: pillWidth,
              height: pillWidth,
              borderRadius: pillWidth / 2,
              backgroundColor: isDark ? 'rgba(75,159,255,0.22)' : 'rgba(0,74,153,0.12)',
              borderColor: isDark ? 'rgba(75,159,255,0.45)' : `${colors.primary}55`,
            },
          ]}
        />
      ) : null}

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
                size={focused ? focusedSize : idleSize}
              />
            </View>
            <Text
              style={{
                marginTop: 4,
                color: focused ? palette.primary : palette.textMuted,
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
            backgroundColor: isDark ? 'rgba(21,26,36,0.92)' : 'rgba(255,255,255,0.92)',
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
    position: 'relative',
  },
  item: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 58,
    zIndex: 2,
  },
  activeLift: {
    transform: [{ translateY: -2 }],
    shadowColor: '#004A99',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.22,
    shadowRadius: 8,
  },
  liquidPill: {
    position: 'absolute',
    top: 6,
    left: 0,
    borderWidth: StyleSheet.hairlineWidth,
    zIndex: 1,
  },
});
