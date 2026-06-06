import React, { useState } from 'react';
import {
  StyleSheet,
  Text,
  TextInput,
  TextInputProps,
  View,
  ViewStyle,
} from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface TextFieldProps extends Omit<TextInputProps, 'style'> {
  label?: string;
  error?: string | null;
  containerStyle?: ViewStyle;
}

export const TextField: React.FC<TextFieldProps> = ({
  label,
  error,
  containerStyle,
  onFocus,
  onBlur,
  ...inputProps
}) => {
  const { palette, colors, radius, spacing, typography, elevation } = useTheme();
  const [focused, setFocused] = useState(false);

  const borderColor = error ? colors.error : focused ? colors.primary : palette.borderSubtle;

  return (
    <View style={[{ marginBottom: spacing.md }, containerStyle]}>
      {label ? (
        <Text
          style={[
            styles.label,
            {
              color: palette.textSecondary,
              fontSize: typography.caption.fontSize,
              marginBottom: spacing.xs,
            },
          ]}
        >
          {label}
        </Text>
      ) : null}
      <TextInput
        placeholderTextColor={palette.textMuted}
        {...inputProps}
        onFocus={(e) => {
          setFocused(true);
          onFocus?.(e);
        }}
        onBlur={(e) => {
          setFocused(false);
          onBlur?.(e);
        }}
        style={[
          styles.input,
          elevation[focused ? 1 : 0],
          {
            borderColor,
            borderRadius: radius.control,
            paddingHorizontal: spacing.md,
            color: palette.textPrimary,
            backgroundColor: palette.surfaceRaised,
            fontSize: typography.body.fontSize,
          },
        ]}
      />
      {error ? (
        <Text
          style={[
            styles.error,
            {
              color: colors.error,
              fontSize: typography.caption.fontSize,
              marginTop: spacing.xs,
            },
          ]}
        >
          {error}
        </Text>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  label: { fontWeight: '500' },
  input: {
    borderWidth: 1,
    minHeight: 48,
  },
  error: { fontWeight: '500' },
});
