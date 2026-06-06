import React from 'react';
import { Pressable, StyleSheet, Text, View, ViewStyle } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface SegmentedTab<T extends string = string> {
  key: T;
  label: string;
}

export interface SegmentedTabBarProps<T extends string = string> {
  tabs: SegmentedTab<T>[];
  activeTab: T;
  onTabChange: (tab: T) => void;
  style?: ViewStyle;
}

/** Unified segmented tab bar — pill container with active fill. */
export function SegmentedTabBar<T extends string>({
  tabs,
  activeTab,
  onTabChange,
  style,
}: SegmentedTabBarProps<T>) {
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();

  return (
    <View
      style={[
        styles.container,
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
      {tabs.map((tab) => {
        const active = activeTab === tab.key;
        return (
          <Pressable
            key={tab.key}
            onPress={() => onTabChange(tab.key)}
            accessibilityRole="tab"
            accessibilityState={{ selected: active }}
            style={[
              styles.tab,
              {
                borderRadius: radius.sm,
                backgroundColor: active ? palette.surfaceRaised : 'transparent',
                paddingVertical: spacing.sm,
              },
              active && elevation[1],
            ]}
          >
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
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flexDirection: 'row', flexWrap: 'wrap' },
  tab: { flex: 1, minWidth: '22%', alignItems: 'center' },
});
