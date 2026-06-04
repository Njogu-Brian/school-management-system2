import React from 'react';
import { StyleSheet, View } from 'react-native';
import { TextField } from '../primitives/TextField';
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
      <TextField
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        autoCapitalize="none"
        autoCorrect={false}
        returnKeyType="search"
      />
    </View>
  );
};

const styles = StyleSheet.create({
  wrap: {},
});
