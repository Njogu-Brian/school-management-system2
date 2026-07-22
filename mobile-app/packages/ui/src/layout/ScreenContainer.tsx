import React from 'react';
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

export interface ScreenContainerProps {
  children: React.ReactNode;
  /** Wrap content in a ScrollView. Pass `false` for screens that own a FlatList. */
  scroll?: boolean;
  style?: StyleProp<ViewStyle>;
  contentContainerStyle?: StyleProp<ViewStyle>;
  scrollProps?: ScrollViewProps;
  edges?: Array<'top' | 'bottom' | 'left' | 'right'>;
  keyboardVerticalOffset?: number;
  /** Extra bottom inset so floating tab bar does not cover actions (default true). */
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
 */
export const ScreenContainer: React.FC<ScreenContainerProps> = ({
  children,
  scroll = true,
  style,
  contentContainerStyle,
  scrollProps,
  edges = ['bottom'],
  keyboardVerticalOffset,
  clearFloatingTabBar = true,
}) => {
  const { palette } = useTheme();
  const insets = useSafeAreaInsets();
  const hasBottomEdge = edges.includes('bottom');
  const tabClearance = useFloatingTabBarClearance(!hasBottomEdge);
  /** Nested FlatLists own their content padding; only pad ScrollView screens here. */
  const bottomClearance = clearFloatingTabBar && scroll ? tabClearance : 0;
  const resolvedPaddingBottom = minPaddingBottom(contentContainerStyle, bottomClearance);

  const body = scroll ? (
    <ScrollView
      style={styles.flex}
      keyboardShouldPersistTaps="handled"
      contentContainerStyle={[
        styles.scrollContent,
        contentContainerStyle,
        bottomClearance ? { paddingBottom: resolvedPaddingBottom } : null,
      ]}
      showsVerticalScrollIndicator={false}
      {...scrollProps}
    >
      {children}
    </ScrollView>
  ) : (
    <View style={[styles.flex, contentContainerStyle]}>{children}</View>
  );

  return (
    <SafeAreaView edges={edges} style={[styles.flex, { backgroundColor: palette.background }, style]}>
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        keyboardVerticalOffset={keyboardVerticalOffset ?? insets.top}
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
