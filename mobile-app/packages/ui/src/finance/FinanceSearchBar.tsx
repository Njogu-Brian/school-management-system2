import React from 'react';
import { View } from 'react-native';
import { SearchBar } from '../primitives/SearchBar';
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
  const { spacing } = useTheme();

  return (
    <View style={{ marginBottom: spacing.sm }}>
      <SearchBar value={value} onChangeText={onChangeText} placeholder={placeholder} />
    </View>
  );
};
