import React from 'react';
import { View } from 'react-native';
import { SearchBar } from '../primitives/SearchBar';
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
  const { spacing } = useTheme();

  return (
    <View style={{ marginBottom: spacing.sm }}>
      <SearchBar value={value} onChangeText={onChangeText} placeholder={placeholder} />
    </View>
  );
};
