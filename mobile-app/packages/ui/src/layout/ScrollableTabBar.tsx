import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View, ViewStyle } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface ScrollableTab<T extends string = string> {
  key: T;
  label: string;
  icon?: keyof typeof Ionicons.glyphMap;
}

export interface ScrollableTabBarProps<T extends string = string> {
  tabs: ScrollableTab<T>[];
  activeTab: T;
  onTabChange: (tab: T) => void;
  /** `scroll` — horizontal pill row (360 profiles). `segmented` — equal-width track (dashboard). */
  variant?: 'scroll' | 'segmented';
  style?: ViewStyle;
}

/**
 * Unified tab bar for 360 profiles, dashboard, and settings.
 * Replaces duplicated pill-tab implementations across the app.
 */
export function ScrollableTabBar<T extends string>({
  tabs,
  activeTab,
  onTabChange,
  variant = 'scroll',
  style,
}: ScrollableTabBarProps<T>) {
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();

  const renderTab = (tab: ScrollableTab<T>) => {
    const active = activeTab === tab.key;
    return (
      <Pressable
        key={tab.key}
        onPress={() => onTabChange(tab.key)}
        accessibilityRole="tab"
        accessibilityState={{ selected: active }}
        style={[
          styles.tab,
          variant === 'scroll' ? styles.scrollTab : styles.segmentedTab,
          {
            borderRadius: variant === 'scroll' ? radius.chip : radius.sm,
            backgroundColor:
              variant === 'scroll'
                ? active
                  ? `${colors.primary}14`
                  : palette.surfaceRaised
                : active
                  ? palette.surfaceRaised
                  : 'transparent',
            borderColor: variant === 'scroll' ? (active ? colors.primary : palette.borderSubtle) : 'transparent',
            paddingVertical: variant === 'scroll' ? spacing.sm : spacing.sm,
            paddingHorizontal: variant === 'scroll' ? spacing.md : spacing.sm,
            minHeight: 44,
            justifyContent: 'center',
          },
          variant === 'segmented' && active && elevation[1],
        ]}
      >
        <View style={styles.tabInner}>
          {tab.icon ? (
            <Ionicons
              name={tab.icon}
              size={14}
              color={active ? colors.primary : palette.textMuted}
              style={tab.label ? { marginRight: 4 } : undefined}
            />
          ) : null}
          <Text
            style={{
              color: active ? colors.primary : palette.textSecondary,
              fontSize: typography.caption.fontSize,
              fontWeight: active ? '700' : '500',
              textAlign: 'center',
            }}
            numberOfLines={1}
          >
            {tab.label}
          </Text>
        </View>
      </Pressable>
    );
  };

  if (variant === 'segmented') {
    return (
      <View
        style={[
          styles.segmentedTrack,
          elevation[1],
          {
            backgroundColor: palette.surfaceMuted,
            borderRadius: radius.control,
            padding: spacing.xs,
            marginBottom: spacing.md,
            gap: spacing.xs,
          },
          style,
        ]}
      >
        {tabs.map(renderTab)}
      </View>
    );
  }

  return (
    <View style={[{ backgroundColor: palette.background, paddingVertical: spacing.sm }, style]}>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: spacing.xs, paddingHorizontal: spacing.xs }}>
        {tabs.map(renderTab)}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  segmentedTrack: { flexDirection: 'row', flexWrap: 'wrap' },
  segmentedTab: { flex: 1, minWidth: '22%', alignItems: 'center' },
  scrollTab: { borderWidth: StyleSheet.hairlineWidth, alignItems: 'center' },
  tab: {},
  tabInner: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center' },
});
