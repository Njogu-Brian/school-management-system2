import { LinearGradient } from 'expo-linear-gradient';
import React from 'react';
import {
  ActivityIndicator,
  Pressable,
  Text,
  ViewStyle,
} from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export type ButtonVariant = 'primary' | 'secondary' | 'outlined' | 'ghost' | 'destructive';

export interface ButtonProps {
  label: string;
  onPress: () => void;
  variant?: ButtonVariant;
  loading?: boolean;
  disabled?: boolean;
  fullWidth?: boolean;
  style?: ViewStyle;
  testID?: string;
}

export const Button: React.FC<ButtonProps> = ({
  label,
  onPress,
  variant = 'primary',
  loading = false,
  disabled = false,
  fullWidth = true,
  style,
  testID,
}) => {
  const { colors, palette, radius, spacing, typography, opacity } = useTheme();
  const isDisabled = disabled || loading;

  const resolved =
    variant === 'primary'
      ? {
          backgroundColor: palette.primary,
          textColor: palette.textOnPrimary,
          borderWidth: 0,
          borderColor: 'transparent',
          gradient: [palette.primary, colors.primaryLight] as [string, string],
        }
      : variant === 'secondary'
        ? {
            backgroundColor: palette.primaryMuted,
            textColor: palette.primary,
            borderWidth: 0,
            borderColor: 'transparent',
            gradient: null,
          }
        : variant === 'outlined'
          ? {
              backgroundColor: 'transparent',
              textColor: palette.primary,
              borderWidth: 1,
              borderColor: palette.primary,
              gradient: null,
            }
          : variant === 'destructive'
            ? {
                backgroundColor: colors.danger,
                textColor: palette.textOnPrimary,
                borderWidth: 0,
                borderColor: 'transparent',
                gradient: [colors.danger, '#b91c1c'] as [string, string],
              }
            : {
                backgroundColor: 'transparent',
                textColor: palette.primary,
                borderWidth: 1,
                borderColor: palette.primary,
                gradient: null,
              };

  const labelNode = loading ? (
    <ActivityIndicator color={resolved.textColor} />
  ) : (
    <Text
      style={{
        color: resolved.textColor,
        fontSize: typography.button.fontSize,
        lineHeight: typography.button.lineHeight,
        fontWeight: typography.button.fontWeight,
        letterSpacing: typography.button.letterSpacing,
      }}
    >
      {label}
    </Text>
  );

  const shape = {
    borderRadius: radius.control,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.lg,
    opacity: isDisabled ? opacity.disabled : 1,
    alignSelf: (fullWidth ? 'stretch' : 'flex-start') as ViewStyle['alignSelf'],
    borderWidth: resolved.borderWidth,
    borderColor: resolved.borderColor,
    minHeight: 52,
    alignItems: 'center' as const,
    justifyContent: 'center' as const,
    overflow: 'hidden' as const,
  };

  if (resolved.gradient) {
    return (
      <Pressable
        testID={testID}
        accessibilityRole="button"
        accessibilityState={{ disabled: isDisabled, busy: loading }}
        disabled={isDisabled}
        onPress={onPress}
        style={({ pressed }) => [{ opacity: isDisabled ? opacity.disabled : pressed ? opacity.pressed : 1 }, style]}
      >
        <LinearGradient colors={resolved.gradient} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={shape}>
          {labelNode}
        </LinearGradient>
      </Pressable>
    );
  }

  return (
    <Pressable
      testID={testID}
      accessibilityRole="button"
      accessibilityState={{ disabled: isDisabled, busy: loading }}
      disabled={isDisabled}
      onPress={onPress}
      style={({ pressed }) => [
        shape,
        {
          backgroundColor: resolved.backgroundColor,
          opacity: isDisabled ? opacity.disabled : pressed ? opacity.pressed : 1,
        },
        style,
      ]}
    >
      {labelNode}
    </Pressable>
  );
};
