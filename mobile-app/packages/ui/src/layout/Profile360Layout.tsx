import { Ionicons } from '@expo/vector-icons';
import React, { useEffect, useRef } from 'react';
import { Animated, Pressable, StyleSheet, Text, View, type NativeSyntheticEvent, type NativeScrollEvent } from 'react-native';
import { ScrollableTabBar, type ScrollableTab } from './ScrollableTabBar';
import { useTheme } from '../theme/ThemeContext';

export type Profile360Tab<T extends string = string> = ScrollableTab<T>;

export interface Profile360TopBar {
  label: string;
  onBack: () => void;
}

export interface Profile360LayoutProps<T extends string = string> {
  header: React.ReactNode;
  /** Shown in a fixed bar when the large header scrolls away. */
  headerCompact?: React.ReactNode;
  tabs: Profile360Tab<T>[];
  activeTab: T;
  onTabChange: (tab: T) => void;
  children: React.ReactNode;
  topBar?: Profile360TopBar;
}

const COLLAPSE_THRESHOLD = 80;

/**
 * Shared 360 profile shell — collapsing header, sticky tabs, tab content.
 * Remembers scroll offset per tab so switching (and returning) restores the view.
 */
export function Profile360Layout<T extends string>({
  header,
  headerCompact,
  tabs,
  activeTab,
  onTabChange,
  children,
  topBar,
}: Profile360LayoutProps<T>) {
  const { palette, spacing, typography } = useTheme();
  const scrollY = useRef(new Animated.Value(0)).current;
  const scrollRef = useRef<Animated.ScrollView>(null);
  const currentY = useRef(0);
  const offsetsByTab = useRef<Partial<Record<T, number>>>({});
  const prevTabRef = useRef(activeTab);

  useEffect(() => {
    if (prevTabRef.current === activeTab) return;
    offsetsByTab.current[prevTabRef.current] = currentY.current;
    const restoreY = offsetsByTab.current[activeTab] ?? 0;
    prevTabRef.current = activeTab;
    requestAnimationFrame(() => {
      scrollRef.current?.scrollTo({ y: restoreY, animated: false });
      currentY.current = restoreY;
      scrollY.setValue(restoreY);
    });
  }, [activeTab, scrollY]);

  const largeOpacity = scrollY.interpolate({
    inputRange: [0, COLLAPSE_THRESHOLD * 0.55, COLLAPSE_THRESHOLD],
    outputRange: [1, 0.35, 0],
    extrapolate: 'clamp',
  });

  const compactOpacity = headerCompact
    ? scrollY.interpolate({
        inputRange: [0, COLLAPSE_THRESHOLD * 0.65, COLLAPSE_THRESHOLD],
        outputRange: [0, 0, 1],
        extrapolate: 'clamp',
      })
    : undefined;

  const onScroll = Animated.event([{ nativeEvent: { contentOffset: { y: scrollY } } }], {
    useNativeDriver: true,
    listener: (e: NativeSyntheticEvent<NativeScrollEvent>) => {
      currentY.current = e.nativeEvent.contentOffset.y;
    },
  });

  return (
    <View style={styles.flex}>
      {topBar ? (
        <View style={[styles.topBar, { paddingHorizontal: spacing.md, paddingTop: spacing.sm }]}>
          <Pressable
            onPress={topBar.onBack}
            accessibilityRole="button"
            accessibilityLabel="Go back"
            hitSlop={8}
            style={({ pressed }) => [
              {
                opacity: pressed ? 0.7 : 1,
                padding: spacing.xs,
                minWidth: 44,
                minHeight: 44,
                justifyContent: 'center',
              },
            ]}
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

      <View style={styles.scrollWrap}>
        {headerCompact && compactOpacity ? (
          <Animated.View
            pointerEvents="none"
            style={[
              styles.compactBar,
              {
                opacity: compactOpacity,
                backgroundColor: palette.surface,
                borderBottomColor: palette.borderSubtle,
                paddingHorizontal: spacing.md,
                paddingVertical: spacing.sm,
              },
            ]}
          >
            {headerCompact}
          </Animated.View>
        ) : null}

        <Animated.ScrollView
          ref={scrollRef}
          contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
          stickyHeaderIndices={[1]}
          scrollEventThrottle={16}
          onScroll={onScroll}
        >
          <Animated.View style={{ opacity: largeOpacity }}>{header}</Animated.View>

          <ScrollableTabBar tabs={tabs} activeTab={activeTab} onTabChange={onTabChange} variant="scroll" />

          <View style={{ marginTop: spacing.sm }}>{children}</View>
        </Animated.ScrollView>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  scrollWrap: { flex: 1, position: 'relative' },
  topBar: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  compactBar: {
    position: 'absolute',
    left: 0,
    right: 0,
    top: 0,
    zIndex: 3,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
});
