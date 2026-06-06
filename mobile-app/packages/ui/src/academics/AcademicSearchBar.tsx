import React from 'react';
import { View } from 'react-native';
import { SearchBar } from '../primitives/SearchBar';
import { useTheme } from '../theme/ThemeContext';

export interface AcademicSearchBarProps {
  value: string;
  onChangeText: (text: string) => void;
  placeholder?: string;
}

export const AcademicSearchBar: React.FC<AcademicSearchBarProps> = ({
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
