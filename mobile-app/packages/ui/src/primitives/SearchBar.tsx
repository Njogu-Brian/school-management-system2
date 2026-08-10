import { Ionicons } from '@expo/vector-icons';
import React, { useEffect, useState } from 'react';
import {
  LayoutAnimation,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  UIManager,
  View,
  ViewStyle,
} from 'react-native';
import { useTheme } from '../theme/ThemeContext';

if (Platform.OS === 'android' && UIManager.setLayoutAnimationEnabledExperimental) {
  UIManager.setLayoutAnimationEnabledExperimental(true);
}

export interface SearchBarProps {
  value: string;
  onChangeText: (text: string) => void;
  placeholder?: string;
  style?: ViewStyle;
  autoFocus?: boolean;
  /**
   * Modern expanding search: starts as a circular icon button and expands into a pill.
   * Pass `expanded` / `onExpandedChange` to control from a parent header.
   */
  expandable?: boolean;
  expanded?: boolean;
  onExpandedChange?: (expanded: boolean) => void;
  /** Optional trailing action label inside the expanded pill (e.g. Search). */
  actionLabel?: string;
  onActionPress?: () => void;
}

/** Unified search bar — V2 design with optional liquid expand-from-circle animation. */
export const SearchBar: React.FC<SearchBarProps> = ({
  value,
  onChangeText,
  placeholder = 'Search…',
  style,
  autoFocus,
  expandable = false,
  expanded: expandedProp,
  onExpandedChange,
  actionLabel,
  onActionPress,
}) => {
  const { palette, spacing, typography, radius, elevation, colors } = useTheme();
  const [focused, setFocused] = useState(false);
  const [internalExpanded, setInternalExpanded] = useState(Boolean(autoFocus));
  const expanded = expandable ? (expandedProp ?? internalExpanded) : true;

  const setExpanded = (next: boolean) => {
    LayoutAnimation.configureNext({
      duration: 420,
      update: { type: LayoutAnimation.Types.easeInEaseOut },
      create: { type: LayoutAnimation.Types.easeInEaseOut, property: LayoutAnimation.Properties.opacity },
    });
    if (expandedProp === undefined) setInternalExpanded(next);
    onExpandedChange?.(next);
  };

  useEffect(() => {
    if (autoFocus && expandable) setExpanded(true);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [autoFocus]);

  if (expandable && !expanded) {
    return (
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Open search"
        onPress={() => setExpanded(true)}
        style={[
          styles.circle,
          elevation[2],
          {
            backgroundColor: palette.surfaceRaised,
            borderColor: palette.borderSubtle,
            marginBottom: spacing.sm,
          },
          style,
        ]}
      >
        <Ionicons name="search-outline" size={22} color={palette.textMain} />
      </Pressable>
    );
  }

  return (
    <View
      style={[
        styles.wrap,
        elevation[focused ? 2 : 1],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: focused ? palette.primary : palette.borderSubtle,
          borderRadius: expandable ? 50 : radius.control,
          paddingHorizontal: spacing.md,
          marginBottom: spacing.sm,
          minHeight: expandable ? 52 : 48,
        },
        style,
      ]}
    >
      <Ionicons name="search-outline" size={20} color={palette.textMuted} />
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={palette.textMuted}
        onFocus={() => setFocused(true)}
        onBlur={() => setFocused(false)}
        style={[
          styles.input,
          {
            color: palette.textMain,
            fontSize: typography.body.fontSize,
            lineHeight: typography.body.lineHeight,
          },
        ]}
        autoCapitalize="none"
        autoCorrect={false}
        autoFocus={autoFocus || (expandable && expanded)}
        returnKeyType="search"
        selectionColor={palette.primary}
        accessibilityRole="search"
        onSubmitEditing={onActionPress}
      />
      {actionLabel ? (
        <Pressable
          accessibilityRole="button"
          onPress={onActionPress}
          style={{
            paddingHorizontal: spacing.sm,
            paddingVertical: 6,
            borderRadius: 999,
            backgroundColor: colors.primary,
            marginRight: value.length > 0 || expandable ? 4 : 0,
          }}
        >
          <Text style={{ color: '#fff', fontWeight: '700', fontSize: typography.caption.fontSize }}>
            {actionLabel}
          </Text>
        </Pressable>
      ) : null}
      {value.length > 0 ? (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Clear search"
          hitSlop={8}
          onPress={() => onChangeText('')}
        >
          <Ionicons name="close-circle" size={20} color={palette.textMuted} />
        </Pressable>
      ) : expandable ? (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Collapse search"
          hitSlop={8}
          onPress={() => setExpanded(false)}
        >
          <Ionicons name="close" size={20} color={palette.textMuted} />
        </Pressable>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
    minHeight: 48,
    gap: 10,
    flex: 1,
  },
  input: { flex: 1, paddingVertical: 12 },
  circle: {
    width: 48,
    height: 48,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
});
