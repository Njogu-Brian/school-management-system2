import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { ScrollableTabBar, type ScrollableTab } from './ScrollableTabBar';
import { useTheme } from '../theme/ThemeContext';

export type Profile360Tab<T extends string = string> = ScrollableTab<T>;

export interface Profile360TopBar {
  label: string;
  onBack: () => void;
}

export interface Profile360LayoutProps<T extends string = string> {
  header: React.ReactNode;
  tabs: Profile360Tab<T>[];
  activeTab: T;
  onTabChange: (tab: T) => void;
  children: React.ReactNode;
  topBar?: Profile360TopBar;
}

/**
 * Shared 360 profile shell — header, sticky scrollable tabs, tab content.
 * Used by Student, Staff, and Admissions detail screens.
 */
export function Profile360Layout<T extends string>({
  header,
  tabs,
  activeTab,
  onTabChange,
  children,
  topBar,
}: Profile360LayoutProps<T>) {
  const { palette, spacing, typography } = useTheme();

  return (
    <View style={styles.flex}>
      {topBar ? (
        <View style={[styles.topBar, { paddingHorizontal: spacing.md, paddingTop: spacing.sm }]}>
          <Pressable
            onPress={topBar.onBack}
            accessibilityRole="button"
            accessibilityLabel="Go back"
            hitSlop={8}
            style={({ pressed }) => [{ opacity: pressed ? 0.7 : 1, padding: spacing.xs, minWidth: 44, minHeight: 44, justifyContent: 'center' }]}
          >
            <Ionicons name="chevron-back" size={24} color={palette.textPrimary} />
          </Pressable>
          <Text
            style={{
              flex: 1,
              fontSize: typography.title.fontSize,
              fontWeight: typography.title.fontWeight,
              color: palette.textPrimary,
            }}
          >
            {topBar.label}
          </Text>
        </View>
      ) : null}

      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        stickyHeaderIndices={[1]}
      >
        {header}

        <ScrollableTabBar tabs={tabs} activeTab={activeTab} onTabChange={onTabChange} variant="scroll" />

        <View style={{ marginTop: spacing.sm }}>{children}</View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  topBar: { flexDirection: 'row', alignItems: 'center', gap: 4 },
});
