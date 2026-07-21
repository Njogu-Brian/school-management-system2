import { Ionicons } from '@expo/vector-icons';
import React, { useState } from 'react';
import { Pressable, StyleSheet, TextInput, View, ViewStyle } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface SearchBarProps {
  value: string;
  onChangeText: (text: string) => void;
  placeholder?: string;
  style?: ViewStyle;
  autoFocus?: boolean;
}

/** Unified search bar — V2 design with icon, elevated surface, and focus ring. */
export const SearchBar: React.FC<SearchBarProps> = ({
  value,
  onChangeText,
  placeholder = 'Search…',
  style,
  autoFocus,
}) => {
  const { palette, spacing, typography, radius, elevation } = useTheme();
  const [focused, setFocused] = useState(false);

  return (
    <View
      style={[
        styles.wrap,
        elevation[focused ? 2 : 1],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: focused ? palette.primary : palette.borderSubtle,
          borderRadius: radius.control,
          paddingHorizontal: spacing.md,
          marginBottom: spacing.sm,
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
        autoFocus={autoFocus}
        returnKeyType="search"
        selectionColor={palette.primary}
        accessibilityRole="search"
      />
      {value.length > 0 ? (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Clear search"
          hitSlop={8}
          onPress={() => onChangeText('')}
        >
          <Ionicons name="close-circle" size={20} color={palette.textMuted} />
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
  },
  input: { flex: 1, paddingVertical: 12 },
});
