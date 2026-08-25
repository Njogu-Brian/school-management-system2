import React from 'react';
import { StyleSheet, View } from 'react-native';
import { SearchBar } from '../primitives/SearchBar';

export interface StudentSearchBarProps {
  value: string;
  onChangeText: (text: string) => void;
  placeholder?: string;
}

export const StudentSearchBar: React.FC<StudentSearchBarProps> = ({
  value,
  onChangeText,
  placeholder = 'Search name or admission no.',
}) => {
  return (
    <View style={styles.wrap}>
      <SearchBar value={value} onChangeText={onChangeText} placeholder={placeholder} />
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: {},
});
