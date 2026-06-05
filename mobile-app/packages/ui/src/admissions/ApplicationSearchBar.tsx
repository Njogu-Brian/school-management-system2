import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, TextInput, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface ApplicationSearchBarProps {
  value: string;
  onChangeText: (text: string) => void;
  placeholder?: string;
}

export const ApplicationSearchBar: React.FC<ApplicationSearchBarProps> = ({
  value,
  onChangeText,
  placeholder = 'Search name or parent phone…',
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <View
      style={[
        styles.wrap,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.lg,
          paddingHorizontal: spacing.md,
          marginBottom: spacing.sm,
        },
      ]}
    >
      <Ionicons name="search-outline" size={18} color={palette.textSecondary} />
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={palette.textSecondary}
        style={[styles.input, { color: palette.textPrimary, fontSize: fontSizes.md }]}
        autoCapitalize="none"
        autoCorrect={false}
        returnKeyType="search"
        selectionColor={colors.primary}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
    minHeight: 44,
    gap: 8,
  },
  input: { flex: 1, paddingVertical: 10 },
});
