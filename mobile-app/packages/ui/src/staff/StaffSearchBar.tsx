import React from 'react';
import { StyleSheet, View } from 'react-native';
import { SearchBar } from '../primitives/SearchBar';
import { useTheme } from '../theme/ThemeContext';

export interface StaffSearchBarProps {
  value: string;
  onChangeText: (text: string) => void;
  placeholder?: string;
}

export const StaffSearchBar: React.FC<StaffSearchBarProps> = ({
  value,
  onChangeText,
  placeholder = 'Search name, ID, email, or phone',
}) => {
  const { spacing } = useTheme();

  return (
    <View style={[styles.wrap, { marginBottom: spacing.sm }]}>
      <SearchBar value={value} onChangeText={onChangeText} placeholder={placeholder} />
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: {},
});
