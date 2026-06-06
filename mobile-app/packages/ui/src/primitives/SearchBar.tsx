import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, TextInput, View, ViewStyle } from 'react-native';
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
  const { palette, colors, spacing, typography, radius, elevation } = useTheme();

  return (
    <View
      style={[
        styles.wrap,
        elevation[1],
        {
          backgroundColor: palette.surfaceRaised,
          borderColor: palette.borderSubtle,
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
        style={[
          styles.input,
          {
            color: palette.textPrimary,
            fontSize: typography.body.fontSize,
            lineHeight: typography.body.lineHeight,
          },
        ]}
        autoCapitalize="none"
        autoCorrect={false}
        autoFocus={autoFocus}
        returnKeyType="search"
        selectionColor={colors.primary}
        accessibilityRole="search"
      />
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
