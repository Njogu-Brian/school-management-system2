import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, TextInput, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface FinanceSearchBarProps {
  value: string;
  onChangeText: (text: string) => void;
  placeholder?: string;
}

export const FinanceSearchBar: React.FC<FinanceSearchBarProps> = ({
  value,
  onChangeText,
  placeholder = 'Search…',
}) => {
  const { palette, spacing, fontSizes, radius } = useTheme();

  return (
    <View
      style={[
        styles.row,
        {
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderRadius: radius.md,
          paddingHorizontal: spacing.sm,
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
        style={{
          flex: 1,
          paddingVertical: spacing.sm,
          paddingHorizontal: spacing.sm,
          color: palette.textPrimary,
          fontSize: fontSizes.md,
        }}
        autoCapitalize="none"
        autoCorrect={false}
        clearButtonMode="while-editing"
      />
    </View>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
});
