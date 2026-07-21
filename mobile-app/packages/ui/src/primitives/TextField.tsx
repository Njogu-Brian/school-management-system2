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
  leftSlot?: React.ReactNode;
  rightSlot?: React.ReactNode;
}

export const TextField: React.FC<TextFieldProps> = ({
  label,
  error,
  containerStyle,
  leftSlot,
  rightSlot,
  onFocus,
  onBlur,
  ...inputProps
}) => {
  const { palette, colors, radius, spacing, typography, elevation } = useTheme();
  const [focused, setFocused] = useState(false);

  const borderColor = error ? colors.error : focused ? palette.primary : palette.borderSubtle;
  const hasSlots = leftSlot != null || rightSlot != null;

  const fieldChrome = {
    borderColor,
    borderRadius: radius.control,
    backgroundColor: palette.surfaceRaised,
  } as const;

  const inputStyle = [
    styles.input,
    !hasSlots && elevation[focused ? 1 : 0],
    !hasSlots && {
      ...fieldChrome,
      paddingHorizontal: spacing.md,
    },
    hasSlots && styles.inputInRow,
    {
      color: palette.textMain,
      fontSize: typography.body.fontSize,
      lineHeight: typography.body.lineHeight,
    },
  ];

  const input = (
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
      style={inputStyle}
    />
  );

  return (
    <View style={[{ marginBottom: spacing.md }, containerStyle]}>
      {label ? (
        <Text
          style={[
            styles.label,
            {
              color: palette.textSub,
              fontSize: typography.label.fontSize,
              lineHeight: typography.label.lineHeight,
              fontWeight: typography.label.fontWeight,
              letterSpacing: typography.label.letterSpacing,
              marginBottom: spacing.xs,
            },
          ]}
        >
          {label}
        </Text>
      ) : null}
      {hasSlots ? (
        <View
          style={[
            styles.row,
            elevation[focused ? 1 : 0],
            fieldChrome,
            { paddingHorizontal: spacing.md, borderWidth: 1 },
          ]}
        >
          {leftSlot ? <View style={styles.slot}>{leftSlot}</View> : null}
          {input}
          {rightSlot ? <View style={styles.slot}>{rightSlot}</View> : null}
        </View>
      ) : (
        input
      )}
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
  inputInRow: {
    flex: 1,
    borderWidth: 0,
    minHeight: 48,
    paddingHorizontal: 0,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    minHeight: 48,
    gap: 8,
  },
  slot: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  error: { fontWeight: '500' },
});
