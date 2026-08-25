import React from 'react';
import { View } from 'react-native';
import { SearchBar } from '../primitives/SearchBar';

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
  return (
    <View>
      <SearchBar value={value} onChangeText={onChangeText} placeholder={placeholder} />
    </View>
  );
};
