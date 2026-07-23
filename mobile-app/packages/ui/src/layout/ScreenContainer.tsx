import React, { useMemo } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  ScrollViewProps,
  StyleProp,
  StyleSheet,
  View,
  ViewStyle,
} from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '../theme/ThemeContext';
import { useFloatingTabBarClearance } from './PremiumTabBar';

const DEFAULT_EDGES: Array<'top' | 'bottom' | 'left' | 'right'> = ['bottom'];

export interface ScreenContainerProps {
  children: React.ReactNode;
  /** Wrap content in a ScrollView. Pass `false` for screens that own a FlatList. */
  scroll?: boolean;
  style?: StyleProp<ViewStyle>;
  contentContainerStyle?: StyleProp<ViewStyle>;
  scrollProps?: ScrollViewProps;
  edges?: Array<'top' | 'bottom' | 'left' | 'right'>;
  keyboardVerticalOffset?: number;
  /** Extra bottom inset so floating tab bar + system nav do not cover actions (default true). */
  clearFloatingTabBar?: boolean;
}

function minPaddingBottom(
  style: StyleProp<ViewStyle> | undefined,
  min: number,
): number {
  const flat = StyleSheet.flatten(style) as ViewStyle | undefined;
  const current = typeof flat?.paddingBottom === 'number' ? flat.paddingBottom : 0;
  return Math.max(current, min);
}

/**
 * Consistent safe-area + keyboard-aware screen wrapper used by every Admin screen.
 * Background resolves from the active theme palette.
 *
 * Always keeps content above the Android/iOS system navigation bar and the
 * floating workspace tab bar.
 */
export const ScreenContainer: React.FC<ScreenContainerProps> = ({
  children,
  scroll = true,
  style,
  contentContainerStyle,
  scrollProps,
  edges = DEFAULT_EDGES,
  keyboardVerticalOffset,
  clearFloatingTabBar = true,
}) => {
  const { palette } = useTheme();
  const insets = useSafeAreaInsets();
  const hasBottomEdge = edges.includes('bottom');
  /**
   * Always include system nav inset in tab clearance. SafeAreaView bottom edge
   * alone is not enough because the floating tab bar overlays the content area.
   */
  const tabClearance = useFloatingTabBarClearance(true);
  const systemNavPad = Math.max(insets.bottom, Platform.OS === 'android' ? 16 : 8);
  const bottomClearance = clearFloatingTabBar
    ? tabClearance
    : hasBottomEdge
      ? 0
      : systemNavPad;
  const resolvedPaddingBottom = minPaddingBottom(contentContainerStyle, bottomClearance);

  const resolvedEdges = useMemo(() => {
    if (edges.includes('bottom')) return edges;
    return [...edges, 'bottom' as const];
  }, [edges]);

  const body = scroll ? (
    <ScrollView
      style={styles.flex}
      keyboardShouldPersistTaps="handled"
      contentContainerStyle={[
        styles.scrollContent,
        contentContainerStyle,
        bottomClearance > 0 ? { paddingBottom: resolvedPaddingBottom } : null,
      ]}
      showsVerticalScrollIndicator={false}
      {...scrollProps}
    >
      {children}
    </ScrollView>
  ) : (
    <View
      style={[
        styles.flex,
        contentContainerStyle,
        clearFloatingTabBar && !hasBottomEdge ? { paddingBottom: systemNavPad } : null,
      ]}
    >
      {children}
    </View>
  );

  return (
    <SafeAreaView
      edges={resolvedEdges}
      style={[styles.flex, { backgroundColor: palette.background }, style]}
    >
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        keyboardVerticalOffset={keyboardVerticalOffset ?? (Platform.OS === 'ios' ? insets.top : 0)}
      >
        {body}
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  scrollContent: { flexGrow: 1 },
});
